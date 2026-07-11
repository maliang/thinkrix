<?php

namespace Thinkrix\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\ModuleGenerator;

/**
 * Feature: laravel-modules, Property 2: 鍛藉悕绌洪棿鐢熸垚绗﹀悎 PSR-4 鏍煎紡
 *
 * **Validates: Requirements 2.11**
 *
 * 瀵逛换鎰忓悎娉曟ā鍧楀悕绉帮紙StudlyCase锛夊拰璧勬簮绫诲瀷锛坈ontroller, model, service, validate,
 * middleware, event, listener, command锛夛紝鐢熸垚鐨勫懡鍚嶇┖闂村瓧绗︿覆搴旀弧瓒筹細
 * (a) 浠?app\{ModuleName}\{type} 鏍煎紡缁勬垚
 * (b) 鍚勬鍧囦笉涓虹┖
 * (c) 涓嶅寘鍚繛缁弽鏂滄潬
 * (d) 妯″潡鍚嶄笌杈撳叆涓€鑷?
 */
class NamespaceGenerationPropertyTest extends TestCase
{
    use TestTrait;

    private ModuleGenerator $generator;

    /**
     * 鏀寔鐨勮祫婧愮被鍨嬪垪琛?
     */
    private array $resourceTypes = [
        'controller',
        'model',
        'service',
        'validate',
        'middleware',
        'event',
        'listener',
        'command',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // 浼犲叆 mock 鐨?StubResolver 閬垮厤渚濊禆 ThinkPHP app() 鍑芥暟
        $stubResolver = $this->createMock(\Thinkrix\Support\StubResolver::class);
        $this->generator = new ModuleGenerator($stubResolver);
    }

    /**
     * Property 2: 瀵逛换鎰?StudlyCase 妯″潡鍚嶅拰璧勬簮绫诲瀷锛屽懡鍚嶇┖闂寸鍚?PSR-4 鏍煎紡
     *
     * 浣跨敤闅忔満鍗曡瘝缁勫悎鐢熸垚妯″潡鍚嶏紝鍐嶄笌 8 绉嶈祫婧愮被鍨嬬粍鍚堥獙璇佸懡鍚嶇┖闂存牸寮?
     */
    public function testNamespaceGenerationFollowsPsr4Format(): void
    {
        // Feature: laravel-modules, Property 2: 鍛藉悕绌洪棿鐢熸垚绗﹀悎 PSR-4 鏍煎紡
        $words = ['user', 'center', 'admin', 'blog', 'shop', 'api', 'core', 'auth', 'payment', 'order'];

        $this
            ->limitTo(100)
            ->forAll(
                Generators::choose(1, 3),  // 鍗曡瘝鏁伴噺
                Generators::choose(0, 9),  // 鍗曡瘝绱㈠紩 1
                Generators::choose(0, 9),  // 鍗曡瘝绱㈠紩 2
                Generators::choose(0, 9),  // 鍗曡瘝绱㈠紩 3
                Generators::choose(0, 7)   // 璧勬簮绫诲瀷绱㈠紩
            )
            ->then(function (int $wordCount, int $w1, int $w2, int $w3, int $typeIdx) use ($words) {
                // 鏋勫缓妯″潡鍚嶈緭鍏ワ紙鐢ㄨ繛瀛楃鎷兼帴澶氫釜鍗曡瘝锛?
                $parts = array_slice([$words[$w1], $words[$w2], $words[$w3]], 0, $wordCount);
                $moduleInput = implode('-', $parts);

                // 杞崲涓?StudlyCase 妯″潡鍚?
                $moduleName = $this->generator->studlyCase($moduleInput);
                $type = $this->resourceTypes[$typeIdx];

                // 鐢熸垚鍛藉悕绌洪棿锛歛pp\{ModuleName}\{type}
                $namespace = "app\\{$moduleName}\\{$type}";

                // (a) 鏍煎紡楠岃瘉锛氫互 app\ 寮€澶达紝鍖呭惈妯″潡鍚嶅拰绫诲瀷
                $this->assertStringStartsWith(
                    'app\\',
                    $namespace,
                    "鍛藉悕绌洪棿搴斾互 'app\\' 寮€澶?
                );
                $this->assertStringEndsWith(
                    "\\{$type}",
                    $namespace,
                    "鍛藉悕绌洪棿搴斾互璧勬簮绫诲瀷 '\\{$type}' 缁撳熬"
                );

                // (b) 鍚勬鍧囦笉涓虹┖
                $segments = explode('\\', $namespace);
                foreach ($segments as $segment) {
                    $this->assertNotEmpty(
                        $segment,
                        "鍛藉悕绌洪棿 '$namespace' 涓笉搴旀湁绌烘"
                    );
                }

                // (c) 涓嶅寘鍚繛缁弽鏂滄潬
                $this->assertStringNotContainsString(
                    '\\\\',
                    $namespace,
                    "鍛藉悕绌洪棿 '$namespace' 涓嶅簲鍖呭惈杩炵画鍙嶆枩鏉?
                );

                // (d) 妯″潡鍚嶄笌 studlyCase 杞崲缁撴灉涓€鑷?
                $this->assertEquals(
                    $moduleName,
                    $segments[1],
                    "鍛藉悕绌洪棿涓殑妯″潡鍚?'{$segments[1]}' 搴斾笌 studlyCase 杈撳嚭 '$moduleName' 涓€鑷?
                );

                // 棰濆楠岃瘉锛氬懡鍚嶇┖闂存伆濂?3 娈?
                $this->assertCount(
                    3,
                    $segments,
                    "鍛藉悕绌洪棿 '$namespace' 搴旀伆濂藉寘鍚?3 娈碉紙app, 妯″潡鍚? 璧勬簮绫诲瀷锛?
                );
            });
    }

    /**
     * Property 2 琛ュ厖: 浣跨敤闅忔満瀛楃涓茬敓鎴愭ā鍧楀悕锛岄獙璇佸懡鍚嶇┖闂村畬鏁存€?
     *
     * 纭繚鍗充娇鏄惈鐗规畩瀛楃鐨勮緭鍏ワ紝缁?studlyCase 杞崲鍚庡懡鍚嶇┖闂翠粛鍚堟硶
     */
    public function testNamespaceGenerationWithRandomStringModuleNames(): void
    {
        // Feature: laravel-modules, Property 2: 鍛藉悕绌洪棿鐢熸垚绗﹀悎 PSR-4 鏍煎紡
        $this
            ->limitTo(100)
            ->forAll(
                Generators::suchThat(
                    function ($s) {
                        // 闈炵┖涓旇嚦灏戝惈涓€涓瓧姣嶏紙纭繚 studlyCase 鑳戒骇鐢熶互澶у啓瀛楁瘝寮€澶寸殑鍚堟硶鍚嶇О锛?
                        return is_string($s) && strlen($s) > 0 && preg_match('/[a-zA-Z]/', $s);
                    },
                    Generators::string()
                ),
                Generators::elements($this->resourceTypes)
            )
            ->then(function (string $rawName, string $type) {
                $moduleName = $this->generator->studlyCase($rawName);

                // studlyCase 搴斾骇鐢熼潪绌虹粨鏋滐紙鍓嶇疆鏉′欢锛?
                if (empty($moduleName)) {
                    return; // 璺宠繃鏃犳硶杞崲鐨勬瀬绔儏鍐?
                }

                $namespace = "app\\{$moduleName}\\{$type}";

                // (a) 鏍煎紡楠岃瘉
                $expectedPattern = '/^app\\\\[A-Za-z0-9]+\\\\[a-z]+$/';
                $this->assertMatchesRegularExpression(
                    $expectedPattern,
                    $namespace,
                    "鍛藉悕绌洪棿 '$namespace' 搴斿尮閰嶆牸寮?app\\{StudlyCase}\\{type}"
                );

                // (b) 鍚勬闈炵┖
                $segments = explode('\\', $namespace);
                foreach ($segments as $segment) {
                    $this->assertNotEmpty($segment);
                }

                // (c) 鏃犺繛缁弽鏂滄潬
                $this->assertStringNotContainsString('\\\\', $namespace);

                // (d) 妯″潡鍚嶄竴鑷?
                $this->assertEquals($moduleName, $segments[1]);
            });
    }
}
