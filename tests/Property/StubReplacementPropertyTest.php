<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Property;

use Eris\TestTrait;
use Eris\Generators;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\StubResolver;

/**
 * StubResolver 鍗犱綅绗︽浛鎹㈠畬鏁存€у睘鎬ф祴璇?
 *
 * // Feature: laravel-modules, Property 4: Stub 鍗犱綅绗︽浛鎹㈠畬鏁存€?
 *
 * **Validates: Requirements 6.3**
 *
 * 瀵逛换鎰忓寘鍚凡鐭ュ崰浣嶇鐨勬ā鏉垮瓧绗︿覆鍜屽搴旂殑鏇挎崲鏄犲皠锛?
 * resolve() 鐨勮緭鍑轰腑涓嶅簲鍐嶅寘鍚换浣曟槧灏勪腑瀹氫箟鐨勫崰浣嶇鏍囪锛?
 * 涓旇緭鍑哄簲鍖呭惈鎵€鏈夋浛鎹㈠€笺€?
 */
class StubReplacementPropertyTest extends TestCase
{
    use TestTrait;

    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pbt_stub_replacement_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'package' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        // 鑷畾涔夌洰褰曚笉闇€瑕佸垱寤猴紝鐢ㄤ簬鍥為€€娴嬭瘯
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * Property 4: resolve() 杈撳嚭涓嶅寘鍚换浣曞崰浣嶇鏍囪锛屼笖鍖呭惈鎵€鏈夋浛鎹㈠€?
     *
     * // Feature: laravel-modules, Property 4: Stub 鍗犱綅绗︽浛鎹㈠畬鏁存€?
     */
    public function testAllPlaceholdersAreReplacedAndValuesPresent(): void
    {
        $this->limitTo(100);

        // 鐢熸垚闅忔満鐨勬浛鎹㈠€硷紙瀛楁瘝鏁板瓧瀛楃涓诧紝淇濊瘉闈炵┖涓斾笉鍚崰浣嶇璇硶锛?
        $valueGenerator = Generators::filter(
            fn($v) => strlen($v) > 0 && !str_contains($v, '{{') && !str_contains($v, '}}'),
            Generators::string()
        );

        $this->forAll(
            $valueGenerator, // MODULE_NAME 鏇挎崲鍊?
            $valueGenerator, // LOWER_NAME 鏇挎崲鍊?
            $valueGenerator, // NAMESPACE 鏇挎崲鍊?
            $valueGenerator, // CLASS_NAME 鏇挎崲鍊?
            $valueGenerator, // TABLE_NAME 鏇挎崲鍊?
            $valueGenerator  // TIMESTAMP 鏇挎崲鍊?
        )->then(function (
            string $moduleName,
            string $lowerName,
            string $namespace,
            string $className,
            string $tableName,
            string $timestamp
        ): void {
            // 瀹氫箟鍗犱綅绗﹀拰鏇挎崲鏄犲皠
            $replacements = [
                '{{MODULE_NAME}}' => $moduleName,
                '{{LOWER_NAME}}'  => $lowerName,
                '{{NAMESPACE}}'   => $namespace,
                '{{CLASS_NAME}}'  => $className,
                '{{TABLE_NAME}}'  => $tableName,
                '{{TIMESTAMP}}'   => $timestamp,
            ];

            // 鏋勫缓鍖呭惈鎵€鏈夊崰浣嶇鐨勬ā鏉垮唴瀹?
            $templateContent = "namespace {{NAMESPACE}};\n"
                . "class {{CLASS_NAME}} {\n"
                . "    // Module: {{MODULE_NAME}}\n"
                . "    // Lower: {{LOWER_NAME}}\n"
                . "    // Table: {{TABLE_NAME}}\n"
                . "    // Time: {{TIMESTAMP}}\n"
                . "}\n";

            // 灏嗘ā鏉垮啓鍏ユ祴璇?Stub 鏂囦欢
            $stubName = 'pbt_test_' . uniqid() . '.stub';
            file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . $stubName, $templateContent);

            $resolver = $this->createResolver();
            $result = $resolver->resolve($stubName, $replacements);

            // 鏂█锛氳緭鍑轰笉搴斿寘鍚换浣曞畾涔夌殑鍗犱綅绗︽爣璁?
            foreach (array_keys($replacements) as $placeholder) {
                $this->assertStringNotContainsString(
                    $placeholder,
                    $result,
                    "杈撳嚭涓笉搴斿寘鍚崰浣嶇 [{$placeholder}]"
                );
            }

            // 鏂█锛氳緭鍑哄簲鍖呭惈鎵€鏈夋浛鎹㈠€?
            foreach ($replacements as $placeholder => $value) {
                $this->assertStringContainsString(
                    $value,
                    $result,
                    "杈撳嚭涓簲鍖呭惈鏇挎崲鍊?[{$value}]锛堝搴斿崰浣嶇 {$placeholder}锛?"
                );
            }

            // 娓呯悊姝ゆ杩唬鍒涘缓鐨?Stub 鏂囦欢
            @unlink($this->packageStubDir . DIRECTORY_SEPARATOR . $stubName);
        });
    }

    /**
     * 鍒涘缓鍙祴璇曠殑 StubResolver 瀹炰緥锛堟敞鍏ユ祴璇曠洰褰曡矾寰勶級
     */
    private function createResolver(): StubResolver
    {
        $packageStubDir = $this->packageStubDir;
        $customStubDir = $this->customStubDir;

        return new class($packageStubDir, $customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };
    }

    /**
     * 閫掑綊鍒犻櫎鐩綍
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
