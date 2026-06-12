<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;

/**
 * Stub 模板发布命令
 *
 * 将包内默认 Stub 模板发布到项目的 stubs/thinkrix-modules/ 目录，
 * 供开发者自定义修改。
 *
 * 用法：
 *   php think thinkrix:module-publish-stubs
 */
class PublishStubsCommand extends BaseModuleCommand
{
    protected function configure()
    {
        $this->setName('thinkrix:module-publish-stubs')
            ->setDescription('发布 Stub 模板到项目目录');
    }

    protected function execute(Input $input, Output $output): int
    {
        $stubResolver = $this->getStubResolver();
        $published = $stubResolver->publishStubs();

        if (empty($published)) {
            $output->writeln("<comment>No stub files to publish.</comment>");
            return 0;
        }

        $output->writeln("<info>Published " . count($published) . " stub file(s):</info>");

        foreach ($published as $filename => $targetPath) {
            $output->writeln("  <info>✓</info> {$filename} → {$targetPath}");
        }

        $output->writeln('');
        $output->writeln("<info>Stubs published successfully. You can now customize them.</info>");

        return 0;
    }
}
