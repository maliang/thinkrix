<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Property;

use Eris\TestTrait;
use Eris\Generators;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\StubResolver;

/**
 * StubResolver 鏂囦欢瑙ｆ瀽浼樺厛绾у睘鎬ф祴璇?
 *
 * // Feature: laravel-modules, Property 3: 鏂囦欢瑙ｆ瀽浼樺厛绾р€斺€旇嚜瀹氫箟鏂囦欢瀛樺湪鏃朵紭鍏堣繑鍥?
 *
 * **Validates: Requirements 6.2, 6.3**
 *
 * 瀵逛换鎰忔枃浠跺悕鏍囪瘑绗︼紝褰撹嚜瀹氫箟鐩綍涓瓨鍦ㄥ悓鍚嶆枃浠舵椂锛?
 * 瑙ｆ瀽鍣ㄥ簲杩斿洖鑷畾涔夎矾寰勶紱褰撹嚜瀹氫箟鏂囦欢涓嶅瓨鍦ㄤ絾榛樿鏂囦欢瀛樺湪鏃讹紝搴旇繑鍥為粯璁よ矾寰勩€?
 */
class FileResolutionPriorityPropertyTest extends TestCase
{
    use TestTrait;

    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pbt_file_resolution_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'package' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        mkdir($this->customStubDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * Property 3a: 褰撹嚜瀹氫箟鐩綍涓瓨鍦ㄥ悓鍚嶆枃浠舵椂锛実etStubPath 杩斿洖鑷畾涔夎矾寰?
     *
     * // Feature: laravel-modules, Property 3: 鏂囦欢瑙ｆ瀽浼樺厛绾р€斺€旇嚜瀹氫箟鏂囦欢瀛樺湪鏃朵紭鍏堣繑鍥?
     */
    public function testCustomFileAlwaysTakesPriority(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::filter(
                fn($name) => preg_match('/^[a-zA-Z0-9_\-]+$/', $name) === 1 && strlen($name) > 0,
                Generators::string()
            )
        )->then(function (string $fileName): void {
            $stubName = $fileName . '.stub';

            // 鍦ㄤ袱涓洰褰曚腑閮藉垱寤烘枃浠?
            file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . $stubName, 'default content');
            file_put_contents($this->customStubDir . DIRECTORY_SEPARATOR . $stubName, 'custom content');

            $resolver = $this->createResolver();
            $resolvedPath = $resolver->getStubPath($stubName);

            // 褰撹嚜瀹氫箟鏂囦欢瀛樺湪鏃讹紝搴斾紭鍏堣繑鍥炶嚜瀹氫箟璺緞
            $this->assertEquals(
                $this->customStubDir . DIRECTORY_SEPARATOR . $stubName,
                $resolvedPath,
                "褰撹嚜瀹氫箟鐩綍瀛樺湪鏂囦欢 [{$stubName}] 鏃讹紝搴斾紭鍏堣繑鍥炶嚜瀹氫箟璺緞"
            );

            // 娓呯悊姝ゆ杩唬鍒涘缓鐨勬枃浠?
            @unlink($this->packageStubDir . DIRECTORY_SEPARATOR . $stubName);
            @unlink($this->customStubDir . DIRECTORY_SEPARATOR . $stubName);
        });
    }

    /**
     * Property 3b: 褰撹嚜瀹氫箟鏂囦欢涓嶅瓨鍦ㄤ絾榛樿鏂囦欢瀛樺湪鏃讹紝getStubPath 杩斿洖榛樿璺緞
     *
     * // Feature: laravel-modules, Property 3: 鏂囦欢瑙ｆ瀽浼樺厛绾р€斺€旇嚜瀹氫箟鏂囦欢瀛樺湪鏃朵紭鍏堣繑鍥?
     */
    public function testFallsBackToDefaultWhenCustomNotExists(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::filter(
                fn($name) => preg_match('/^[a-zA-Z0-9_\-]+$/', $name) === 1 && strlen($name) > 0,
                Generators::string()
            )
        )->then(function (string $fileName): void {
            $stubName = $fileName . '.stub';

            // 浠呭湪榛樿鐩綍涓垱寤烘枃浠讹紝涓嶅湪鑷畾涔夌洰褰曚腑鍒涘缓
            file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . $stubName, 'default content');

            // 纭繚鑷畾涔夌洰褰曚腑涓嶅瓨鍦ㄨ鏂囦欢
            $customFile = $this->customStubDir . DIRECTORY_SEPARATOR . $stubName;
            if (file_exists($customFile)) {
                unlink($customFile);
            }

            $resolver = $this->createResolver();
            $resolvedPath = $resolver->getStubPath($stubName);

            // 鑷畾涔変笉瀛樺湪鏃跺簲鍥為€€鍒伴粯璁よ矾寰?
            $this->assertEquals(
                $this->packageStubDir . DIRECTORY_SEPARATOR . $stubName,
                $resolvedPath,
                "褰撹嚜瀹氫箟鐩綍涓嶅瓨鍦ㄦ枃浠?[{$stubName}] 鏃讹紝搴斿洖閫€杩斿洖榛樿璺緞"
            );

            // 娓呯悊
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
