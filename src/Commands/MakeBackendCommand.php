<?php

namespace Thinkrix\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use Thinkrix\Services\ModuleService;

class MakeBackendCommand extends Command
{
    protected function configure()
    {
        $this->setName('thinkrix:make-backend')
            ->setDescription('创建独立的二级后台模块')
            ->addArgument('name', Argument::REQUIRED, '二级后台名称')
            ->addOption('path', 'p', Option::VALUE_OPTIONAL, '路由路径')
            ->addOption('api-prefix', null, Option::VALUE_OPTIONAL, 'API 前缀')
            ->addOption('title', null, Option::VALUE_OPTIONAL, '标题')
            ->addOption('admin-username', null, Option::VALUE_OPTIONAL, '初始管理员用户名')
            ->addOption('admin-password', null, Option::VALUE_OPTIONAL, '初始管理员密码');
    }

    protected function execute(Input $input, Output $output)
    {
        $name = ucfirst($input->getArgument('name'));
        if (!preg_match('/^[A-Z][A-Za-z0-9_]*$/', $name)) {
            $output->error('模块名称只能包含字母、数字和下划线，且必须以字母开头。');
            return 1;
        }

        $lowerName = strtolower($name);
        $path = $input->getOption('path') ?: '/' . $lowerName;
        $apiPrefix = $input->getOption('api-prefix') ?: 'api/' . $lowerName;
        $title = $input->getOption('title') ?: $name . '管理系统';
        $adminUsername = $input->getOption('admin-username') ?: 'admin';
        $adminPassword = $input->getOption('admin-password');
        if (!$adminPassword && $input->isInteractive()) {
            $adminPassword = $output->askHidden($input, '初始管理员密码（至少 8 位）', function ($value) {
                if (!is_string($value) || strlen($value) < 8) {
                    throw new \InvalidArgumentException('管理员密码至少需要 8 位。');
                }
                return $value;
            });
        }
        $adminPassword = $adminPassword ?: bin2hex(random_bytes(12));
        if (strlen($adminPassword) < 8) {
            $output->error('管理员密码至少需要 8 位。');
            return 1;
        }
        $adminPasswordHash = password_hash($adminPassword, PASSWORD_DEFAULT);

        $output->info("创建二级后台模块: {$name}");
        $output->writeln("  路径: {$path}");
        $output->writeln("  API前缀: {$apiPrefix}");
        $output->writeln("  标题: {$title}");

        // 存根目录
        $stubsDir = __DIR__ . '/../../stubs/backend';
        if (!is_dir($stubsDir)) {
            $output->error('存根目录不存在');
            return 1;
        }

        // 模块目录
        $moduleDir = $this->app->getRootPath() . 'app' . DIRECTORY_SEPARATOR . $name;
        if (is_dir($moduleDir)) {
            $output->error("二级后台模块 {$name} 已存在。");
            return 1;
        }
        $this->mkDir($moduleDir);
        $this->mkDir($moduleDir . DIRECTORY_SEPARATOR . 'controller');
        $this->mkDir($moduleDir . DIRECTORY_SEPARATOR . 'model');
        $this->mkDir($moduleDir . DIRECTORY_SEPARATOR . 'middleware');
        $this->mkDir($moduleDir . DIRECTORY_SEPARATOR . 'route');
        $this->mkDir($moduleDir . DIRECTORY_SEPARATOR . 'config');
        $this->mkDir($moduleDir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');

        // 创建模块配置文件
        $configContent = $this->parseStub($stubsDir . DIRECTORY_SEPARATOR . 'config.stub', [
            '{{NAME}}' => $name,
            '{{TITLE}}' => $title,
            '{{PATH}}' => $path,
            '{{API_PREFIX}}' => $apiPrefix,
            '{{LOWER_NAME}}' => $lowerName,
        ]);
        file_put_contents($moduleDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php', $configContent);

        // 创建模块 JSON
        $moduleJsonContent = $this->parseStub($stubsDir . DIRECTORY_SEPARATOR . 'module.json.stub', [
            '{{NAME}}' => $name,
            '{{TITLE}}' => $title,
        ]);
        file_put_contents($moduleDir . DIRECTORY_SEPARATOR . 'module.json', $moduleJsonContent);

        // 创建其他存根文件
        $stubFiles = [
            'route/app.php' => 'routes.stub',
            'controller/Index.php' => 'auth_controller.stub',
            'controller/Menu.php' => 'menu_controller.stub',
            'controller/Permission.php' => 'permission_controller.stub',
            'controller/Role.php' => 'role_controller.stub',
            'controller/User.php' => 'user_controller.stub',
            'controller/System.php' => 'system_controller.stub',
            'middleware/BackendContext.php' => 'backend_context_middleware.stub',
            'model/AdminUser.php' => 'user_model.stub',
            'database/migrations/' . date('YmdHis') . '_create_' . $lowerName . '_admin_users_table.php' => 'user_migration.stub',
        ];

        foreach ($stubFiles as $target => $stub) {
            $stubPath = $stubsDir . DIRECTORY_SEPARATOR . $stub;
            if (file_exists($stubPath)) {
                $content = $this->parseStub($stubPath, [
                    '{{NAME}}' => $name,
                    '{{TITLE}}' => $title,
                    '{{PATH}}' => $path,
                    '{{API_PREFIX}}' => $apiPrefix,
                    '{{LOWER_NAME}}' => $lowerName,
                    '{{namespace}}' => "app\\{$name}\\controller",
                    '{{ADMIN_USERNAME}}' => $adminUsername,
                    '{{ADMIN_PASSWORD_HASH}}' => $adminPasswordHash,
                    '{{MIGRATION_NAME}}' => ucfirst($lowerName),
                ]);
                $this->mkDir(dirname($moduleDir . DIRECTORY_SEPARATOR . $target));
                file_put_contents($moduleDir . DIRECTORY_SEPARATOR . $target, $content);
            }
        }

        try {
            ModuleService::make()->syncModules();
        } catch (\Throwable $e) {
            $output->writeln('<comment>模块已生成，但数据库注册同步失败: ' . $e->getMessage() . '</comment>');
        }

        $output->info("二级后台模块 {$name} 创建完成。");
        $output->writeln("初始管理员用户名: {$adminUsername}");
        $output->writeln("一次性管理员密码: {$adminPassword}");
        $output->writeln("请运行: php think thinkrix:module-migrate {$name}");
        return 0;
    }

    protected function parseStub(string $path, array $replacements): string
    {
        if (!file_exists($path)) { return ''; }
        return str_replace(array_keys($replacements), array_values($replacements), file_get_contents($path));
    }

    protected function mkDir(string $path): void
    {
        if (!is_dir($path)) { mkdir($path, 0755, true); }
    }
}
