<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\ListModuleCommand;

/**
 * ListModuleCommand 鍗曞厓娴嬭瘯
 *
 * 楠岃瘉鍛戒护閰嶇疆姝ｇ‘鎬у拰琛ㄦ牸杈撳嚭閫昏緫銆?
 *
 * Requirements: 3.4
 */
class ListModuleCommandTest extends TestCase
{
    /**
     * 娴嬭瘯鍛戒护鍚嶇О閰嶇疆姝ｇ‘
     */
    public function testCommandNameIsCorrect(): void
    {
        $command = new ListModuleCommand();
        $this->assertEquals('thinkrix:module-list', $command->getName());
    }

    /**
     * 娴嬭瘯鍛戒护鎻忚堪宸查厤缃?
     */
    public function testCommandHasDescription(): void
    {
        $command = new ListModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 娴嬭瘯鍛戒护涓嶉渶瑕佷换浣曞弬鏁?
     */
    public function testCommandHasNoArguments(): void
    {
        $command = new ListModuleCommand();
        $definition = $command->getDefinition();

        $this->assertCount(0, $definition->getArguments());
    }

    /**
     * 娴嬭瘯鍛戒护涓嶉渶瑕佷换浣曡嚜瀹氫箟閫夐」锛堥櫎鍐呯疆閫夐」澶栵級
     */
    public function testCommandHasNoCustomOptions(): void
    {
        $command = new ListModuleCommand();
        $definition = $command->getDefinition();

        // ThinkPHP/Symfony 鍐呯疆閫夐」鍒楄〃
        $builtinOptions = ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction'];

        $options = $definition->getOptions();
        $customOptions = array_filter($options, function ($option) use ($builtinOptions) {
            return !in_array($option->getName(), $builtinOptions);
        });

        $this->assertEmpty($customOptions, '鍛戒护涓嶅簲鏈夎嚜瀹氫箟閫夐」');
    }
}
