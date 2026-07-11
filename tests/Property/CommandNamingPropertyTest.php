<?php

namespace Thinkrix\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\ModuleGenerator;

/**
 * Feature: laravel-modules, Property 5: 鍛戒护鍛藉悕鏍煎紡涓€鑷存€?
 *
 * **Validates: Requirements 10.5**
 *
 * 瀵逛换鎰忓悎娉曠殑妯″潡鍚嶇О鍜屽懡浠ゅ悕绉帮紝鐢熸垚鐨勫懡浠ゆ爣璇嗙搴斾弗鏍奸伒寰?
 * `{module_lower_name}:{command_lower_name}` 鏍煎紡锛屽叾涓細
 * - module_lower_name = strtolower(studlyCase(module_input))锛岀函灏忓啓瀛楁瘝/鏁板瓧
 * - command_lower_name = toSnakeCase(command_input)锛岀函灏忓啓瀛楁瘝/鏁板瓧/涓嬪垝绾?
 * - 涓ら儴鍒嗕互鍐掑彿鍒嗛殧锛屾暣涓爣璇嗙涓伆濂芥湁涓€涓啋鍙?
 */
class CommandNamingPropertyTest extends TestCase
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
     * 妯℃嫙 ModuleGenerator::toSnakeCase() 琛屼负
     *
     * 璇ユ柟娉曚负 protected锛屾澶勫鍒跺叾閫昏緫浠ュ湪娴嬭瘯涓獙璇佸懡鍚嶆牸寮忋€?
     * toSnakeCase 鐨勫疄鐜帮細鍏?studlyCase锛岀劧鍚庡湪澶у啓瀛楁瘝鍓嶆彃鍏ヤ笅鍒掔嚎锛屾渶鍚庤浆灏忓啓銆?
     */
    private function computeSnakeCase(string $name): string
    {
        $studly = $this->generator->studlyCase($name);

        // 鍦ㄥぇ鍐欏瓧姣嶅墠鎻掑叆涓嬪垝绾匡紙棣栧瓧姣嶉櫎澶栵級
        $snake = preg_replace('/([a-z\d])([A-Z])/', '$1_$2', $studly);

        return strtolower($snake);
    }

    /**
     * 鏋勫缓鍛戒护鏍囪瘑绗︼紝妯℃嫙 command.stub 涓殑 {{LOWER_NAME}}:{{TABLE_NAME}} 鏍煎紡
     *
     * @param string $moduleInput 妯″潡鍚嶇О杈撳叆
     * @param string $commandInput 鍛戒护鍚嶇О杈撳叆
     * @return string 鍛戒护鏍囪瘑绗?
     */
    private function buildCommandIdentifier(string $moduleInput, string $commandInput): string
    {
        // LOWER_NAME = strtolower(studlyCase(module_input))
        $moduleLower = strtolower($this->generator->studlyCase($moduleInput));

        // TABLE_NAME = toSnakeCase(command_input)
        $commandSnake = $this->computeSnakeCase($commandInput);

        return "{$moduleLower}:{$commandSnake}";
    }

    /**
     * Property 5: 浣跨敤鍏稿瀷妯″潡鍚嶅拰鍛戒护鍚嶇粍鍚堬紝楠岃瘉鍛戒护鏍囪瘑绗︽牸寮忎竴鑷存€?
     *
     * 浣跨敤棰勫畾涔夊崟璇嶅垪琛ㄤ笌闅忔満鍒嗛殧绗︾粍鍚堢敓鎴愭ā鍧楀悕鍜屽懡浠ゅ悕锛?
     * 楠岃瘉鐢熸垚鐨勫懡浠ゆ爣璇嗙涓ユ牸閬靛惊 {module_lower}:{command_snake} 鏍煎紡銆?
     */
    public function testCommandNamingFormatWithTypicalInputs(): void
    {
        // Feature: laravel-modules, Property 5: 鍛戒护鍛藉悕鏍煎紡涓€鑷存€?
        $moduleWords = ['user', 'center', 'admin', 'blog', 'shop', 'api', 'core', 'auth', 'payment', 'order'];
        $commandWords = ['sync', 'data', 'clear', 'cache', 'import', 'export', 'generate', 'report', 'send', 'notify'];
        $separators = ['-', '_', ' '];

        $this
            ->limitTo(100)
            ->forAll(
                Generators::choose(1, 3),  // 妯″潡鍗曡瘝鏁伴噺
                Generators::choose(0, 2),  // 妯″潡鍒嗛殧绗︾储寮?
                Generators::choose(0, 9),  // 妯″潡鍗曡瘝绱㈠紩 1
                Generators::choose(0, 9),  // 妯″潡鍗曡瘝绱㈠紩 2
                Generators::choose(0, 9),  // 妯″潡鍗曡瘝绱㈠紩 3
                Generators::choose(1, 3),  // 鍛戒护鍗曡瘝鏁伴噺
                Generators::choose(0, 2),  // 鍛戒护鍒嗛殧绗︾储寮?
                Generators::choose(0, 9),  // 鍛戒护鍗曡瘝绱㈠紩 1
                Generators::choose(0, 9),  // 鍛戒护鍗曡瘝绱㈠紩 2
                Generators::choose(0, 9)   // 鍛戒护鍗曡瘝绱㈠紩 3
            )
            ->then(function (
                int $modWordCount,
                int $modSepIdx,
                int $mw1,
                int $mw2,
                int $mw3,
                int $cmdWordCount,
                int $cmdSepIdx,
                int $cw1,
                int $cw2,
                int $cw3
            ) use ($moduleWords, $commandWords, $separators) {
                // 鏋勫缓妯″潡鍚嶈緭鍏?
                $modSep = $separators[$modSepIdx];
                $modParts = array_slice([$moduleWords[$mw1], $moduleWords[$mw2], $moduleWords[$mw3]], 0, $modWordCount);
                $moduleInput = implode($modSep, $modParts);

                // 鏋勫缓鍛戒护鍚嶈緭鍏?
                $cmdSep = $separators[$cmdSepIdx];
                $cmdParts = array_slice([$commandWords[$cw1], $commandWords[$cw2], $commandWords[$cw3]], 0, $cmdWordCount);
                $commandInput = implode($cmdSep, $cmdParts);

                // 鐢熸垚鍛戒护鏍囪瘑绗?
                $identifier = $this->buildCommandIdentifier($moduleInput, $commandInput);

                // 鏂█锛氭伆濂藉寘鍚竴涓啋鍙峰垎闅旂
                $colonCount = substr_count($identifier, ':');
                $this->assertEquals(
                    1,
                    $colonCount,
                    "鍛戒护鏍囪瘑绗?'$identifier' 搴旀伆濂藉寘鍚竴涓啋鍙凤紝瀹為檯鏈?$colonCount 涓?
                );

                // 鎷嗗垎涓烘ā鍧楅儴鍒嗗拰鍛戒护閮ㄥ垎
                [$modulePart, $commandPart] = explode(':', $identifier);

                // 鏂█锛氭ā鍧楅儴鍒嗕笉涓虹┖
                $this->assertNotEmpty(
                    $modulePart,
                    "鍛戒护鏍囪瘑绗?'$identifier' 鐨勬ā鍧楅儴鍒嗕笉搴斾负绌?
                );

                // 鏂█锛氬懡浠ら儴鍒嗕笉涓虹┖
                $this->assertNotEmpty(
                    $commandPart,
                    "鍛戒护鏍囪瘑绗?'$identifier' 鐨勫懡浠ら儴鍒嗕笉搴斾负绌?
                );

                // 鏂█锛氭ā鍧楅儴鍒嗕负绾皬鍐欏瓧姣嶅拰鏁板瓧锛堟棤澶у啓銆佹棤鐗规畩瀛楃锛?
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9]+$/',
                    $modulePart,
                    "鍛戒护鏍囪瘑绗?'$identifier' 鐨勬ā鍧楅儴鍒?'$modulePart' 搴斾粎鍖呭惈灏忓啓瀛楁瘝鍜屾暟瀛?
                );

                // 鏂█锛氬懡浠ら儴鍒嗕负灏忓啓瀛楁瘝銆佹暟瀛楀拰涓嬪垝绾匡紙snake_case 鏍煎紡锛?
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9][a-z0-9_]*[a-z0-9]$|^[a-z0-9]$/',
                    $commandPart,
                    "鍛戒护鏍囪瘑绗?'$identifier' 鐨勫懡浠ら儴鍒?'$commandPart' 搴斾负鍚堟硶 snake_case 鏍煎紡"
                );

                // 鏂█锛氬懡浠ら儴鍒嗕笉鍖呭惈杩炵画涓嬪垝绾?
                $this->assertStringNotContainsString(
                    '__',
                    $commandPart,
                    "鍛戒护鏍囪瘑绗?'$identifier' 鐨勫懡浠ら儴鍒?'$commandPart' 涓嶅簲鍖呭惈杩炵画涓嬪垝绾?
                );

                // 鏂█锛氭棤澶у啓瀛楁瘝
                $this->assertEquals(
                    strtolower($identifier),
                    $identifier,
                    "鍛戒护鏍囪瘑绗?'$identifier' 涓嶅簲鍖呭惈澶у啓瀛楁瘝"
                );
            });
    }

    /**
     * Property 5 琛ュ厖: 浣跨敤闅忔満瀛楃涓茶緭鍏ラ獙璇佸懡浠ゅ懡鍚嶆牸寮忕殑椴佹鎬?
     *
     * 浣跨敤 Eris 鐨勯殢鏈哄瓧绗︿覆鐢熸垚鍣紝杩囨护鑷冲皯鍖呭惈涓€涓瓧姣嶇殑瀛楃涓诧紝
     * 楠岃瘉鍗充娇杈撳叆涓嶈鑼冿紝鐢熸垚鐨勫懡浠ゆ爣璇嗙浠嶆弧瓒虫牸寮忕害鏉熴€?
     */
    public function testCommandNamingFormatWithRandomStrings(): void
    {
        // Feature: laravel-modules, Property 5: 鍛戒护鍛藉悕鏍煎紡涓€鑷存€?
        $this
            ->limitTo(100)
            ->forAll(
                Generators::suchThat(
                    function ($s) {
                        // 鑷冲皯鍖呭惈涓€涓瓧姣嶏紝纭繚 studlyCase 鑳戒骇鐢熸湁鏁堣緭鍑?
                        return is_string($s) && strlen($s) > 0 && preg_match('/[a-zA-Z]/', $s);
                    },
                    Generators::string()
                ),
                Generators::suchThat(
                    function ($s) {
                        // 鑷冲皯鍖呭惈涓€涓瓧姣嶏紝纭繚 toSnakeCase 鑳戒骇鐢熸湁鏁堣緭鍑?
                        return is_string($s) && strlen($s) > 0 && preg_match('/[a-zA-Z]/', $s);
                    },
                    Generators::string()
                )
            )
            ->then(function (string $moduleInput, string $commandInput) {
                $moduleName = $this->generator->studlyCase($moduleInput);
                $commandStudly = $this->generator->studlyCase($commandInput);

                // 璺宠繃鏃犳硶浜х敓鏈夋晥 studlyCase 杈撳嚭鐨勬瀬绔儏鍐?
                if (empty($moduleName) || empty($commandStudly)) {
                    return;
                }

                // 鐢熸垚鍛戒护鏍囪瘑绗?
                $identifier = $this->buildCommandIdentifier($moduleInput, $commandInput);

                // 鏂█锛氭伆濂藉寘鍚竴涓啋鍙?
                $colonCount = substr_count($identifier, ':');
                $this->assertEquals(
                    1,
                    $colonCount,
                    "鍛戒护鏍囪瘑绗?'$identifier'锛堣緭鍏? module='$moduleInput', command='$commandInput'锛夊簲鎭板ソ鍖呭惈涓€涓啋鍙?
                );

                // 鎷嗗垎楠岃瘉
                [$modulePart, $commandPart] = explode(':', $identifier);

                // 鏂█锛氫袱閮ㄥ垎鍧囦笉涓虹┖
                $this->assertNotEmpty($modulePart, "妯″潡閮ㄥ垎涓嶅簲涓虹┖");
                $this->assertNotEmpty($commandPart, "鍛戒护閮ㄥ垎涓嶅簲涓虹┖");

                // 鏂█锛氭ā鍧楅儴鍒嗕负绾皬鍐欏瓧姣?鏁板瓧
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9]+$/',
                    $modulePart,
                    "妯″潡閮ㄥ垎 '$modulePart' 搴斾粎鍚皬鍐欏瓧姣嶅拰鏁板瓧锛堣緭鍏? '$moduleInput'锛?
                );

                // 鏂█锛氬懡浠ら儴鍒嗕负灏忓啓瀛楁瘝/鏁板瓧/涓嬪垝绾?
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9_]+$/',
                    $commandPart,
                    "鍛戒护閮ㄥ垎 '$commandPart' 搴斾粎鍚皬鍐欏瓧姣嶃€佹暟瀛楀拰涓嬪垝绾匡紙杈撳叆: '$commandInput'锛?
                );

                // 鏂█锛氭暣涓爣璇嗙鏃犲ぇ鍐欏瓧姣?
                $this->assertEquals(
                    strtolower($identifier),
                    $identifier,
                    "鍛戒护鏍囪瘑绗?'$identifier' 涓嶅簲鍖呭惈浠讳綍澶у啓瀛楁瘝"
                );

                // 鏂█锛氭ā鍧楅儴鍒嗙瓑浜?strtolower(studlyCase(moduleInput))
                $expectedModulePart = strtolower($moduleName);
                $this->assertEquals(
                    $expectedModulePart,
                    $modulePart,
                    "妯″潡閮ㄥ垎 '$modulePart' 搴旂瓑浜?strtolower(studlyCase('$moduleInput')) = '$expectedModulePart'"
                );
            });
    }
}
