<?php

namespace Thinkrix\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\ModuleGenerator;

/**
 * Feature: laravel-modules, Property 1: StudlyCase 杞崲濮嬬粓浜х敓鍚堟硶鐩綍鍚?
 *
 * **Validates: Requirements 1.3**
 *
 * 瀵逛换鎰忛潪绌哄瓧绗︿覆杈撳叆锛孧oduleGenerator::studlyCase() 鐨勮緭鍑哄簲濮嬬粓锛?
 * (a) 浠ュぇ鍐欏瓧姣嶅紑澶?
 * (b) 浠呭寘鍚瓧姣嶅拰鏁板瓧锛堟棤绌烘牸銆佷笅鍒掔嚎銆佺壒娈婂瓧绗︼級
 * (c) 闀垮害澶т簬 0
 */
class StudlyCasePropertyTest extends TestCase
{
    use TestTrait;

    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        // 浼犲叆 mock 鐨?StubResolver 閬垮厤渚濊禆 ThinkPHP app() 鍑芥暟
        $stubResolver = $this->createMock(\Thinkrix\Support\StubResolver::class);
        $this->generator = new ModuleGenerator($stubResolver);
    }

    /**
     * Property 1: 瀵归殢鏈哄瓧绗︿覆杈撳叆锛宻tudlyCase 杈撳嚭濮嬬粓鏄悎娉曠洰褰曞悕
     *
     * 浣跨敤 Eris 鐨?string() 鐢熸垚鍣紝杩囨护闈炵┖涓旇嚦灏戝惈涓€涓瓧姣嶆垨鏁板瓧鐨勫瓧绗︿覆
     */
    public function testStudlyCaseAlwaysProducesValidDirectoryName(): void
    {
        // Feature: laravel-modules, Property 1: StudlyCase 杞崲濮嬬粓浜х敓鍚堟硶鐩綍鍚?
        $this
            ->limitTo(100)
            ->forAll(
                Generators::suchThat(
                    function ($s) {
                        // 闈炵┖瀛楃涓蹭笖鑷冲皯鍖呭惈涓€涓瓧姣嶏紙纭繚 studlyCase 鑳戒骇鐢熶互澶у啓寮€澶寸殑缁撴灉锛?
                        return is_string($s) && strlen($s) > 0 && preg_match('/[a-zA-Z]/', $s);
                    },
                    Generators::string()
                )
            )
            ->then(function (string $input) {
                $result = $this->generator->studlyCase($input);

                // (c) 闀垮害澶т簬 0
                $this->assertNotEmpty(
                    $result,
                    "studlyCase('$input') 搴斾骇鐢熼潪绌鸿緭鍑?
                );

                // (a) 浠ュぇ鍐欏瓧姣嶅紑澶?
                $this->assertMatchesRegularExpression(
                    '/^[A-Z]/',
                    $result,
                    "studlyCase('$input') = '$result' 搴斾互澶у啓瀛楁瘝寮€澶?
                );

                // (b) 浠呭寘鍚瓧姣嶅拰鏁板瓧
                $this->assertMatchesRegularExpression(
                    '/^[A-Za-z0-9]+$/',
                    $result,
                    "studlyCase('$input') = '$result' 搴斾粎鍖呭惈瀛楁瘝鍜屾暟瀛?
                );
            });
    }

    /**
     * Property 1 琛ュ厖: 浣跨敤鍚┖鏍笺€佷笅鍒掔嚎銆佽繛瀛楃銆佹暟瀛楃殑鑷畾涔夌敓鎴愬櫒
     *
     * 纭繚鍏稿瀷妯″潡鍚嶇О杈撳叆锛堝 user-center銆乵y_module 绛夛級鑳芥纭浆鎹?
     */
    public function testStudlyCaseWithTypicalModuleNameInputs(): void
    {
        // Feature: laravel-modules, Property 1: StudlyCase 杞崲濮嬬粓浜х敓鍚堟硶鐩綍鍚?
        $separators = [' ', '_', '-'];
        $words = ['user', 'center', 'my', 'module', 'admin', 'api', 'test', 'app', 'core', 'data'];

        $this
            ->limitTo(100)
            ->forAll(
                Generators::choose(1, 4),  // 鍗曡瘝鏁伴噺 1-4
                Generators::choose(0, 2),  // 鍒嗛殧绗︾储寮?
                Generators::choose(0, 9),  // 绗竴涓崟璇嶇储寮?
                Generators::choose(0, 9),  // 绗簩涓崟璇嶇储寮?
                Generators::choose(0, 9),  // 绗笁涓崟璇嶇储寮?
                Generators::choose(0, 9)   // 绗洓涓崟璇嶇储寮?
            )
            ->then(function (int $wordCount, int $sepIdx, int $w1, int $w2, int $w3, int $w4) use ($separators, $words) {
                $sep = $separators[$sepIdx];
                $parts = array_slice([$words[$w1], $words[$w2], $words[$w3], $words[$w4]], 0, $wordCount);
                $input = implode($sep, $parts);

                $result = $this->generator->studlyCase($input);

                // (c) 闀垮害澶т簬 0
                $this->assertNotEmpty($result);

                // (a) 浠ュぇ鍐欏瓧姣嶅紑澶?
                $this->assertMatchesRegularExpression(
                    '/^[A-Z]/',
                    $result,
                    "studlyCase('$input') = '$result' 搴斾互澶у啓瀛楁瘝寮€澶?
                );

                // (b) 浠呭寘鍚瓧姣嶅拰鏁板瓧
                $this->assertMatchesRegularExpression(
                    '/^[A-Za-z0-9]+$/',
                    $result,
                    "studlyCase('$input') = '$result' 搴斾粎鍖呭惈瀛楁瘝鍜屾暟瀛?
                );
            });
    }

    public function testStudlyCasePreservesCamelCaseWordBoundaries(): void
    {
        $this->assertSame('UserCenter', $this->generator->studlyCase('userCenter'));
        $this->assertSame('Order2Detail', $this->generator->studlyCase('order2Detail'));
    }
}
