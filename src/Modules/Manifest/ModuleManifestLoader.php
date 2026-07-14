<?php

namespace Thinkrix\Modules\Manifest;

use InvalidArgumentException;
use JsonException;

/** 从 Thinkrix 原生 module.json 的 trix 节点加载并校验生态清单。 */
final class ModuleManifestLoader
{
    /** 加载模块清单；没有 trix 节点时表示它不是生态模块。 */
    public function loadFromPath(string $path): ?ModuleManifest
    {
        $manifestPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'module.json';
        if (!is_file($manifestPath)) {
            return null;
        }

        $module = $this->readJsonFile($manifestPath);
        if (!array_key_exists('trix', $module)) {
            return null;
        }
        if (!is_array($module['trix'])) {
            throw new InvalidArgumentException("Invalid module manifest {$manifestPath}: trix must be an object.");
        }

        $errors = ModuleManifestValidator::validate($module['trix']);
        if ($errors !== []) {
            $messages = [];
            foreach ($errors as $field => $message) {
                $messages[] = "trix.{$field}: {$message}";
            }
            throw new InvalidArgumentException("Invalid module manifest {$manifestPath}: " . implode('; ', $messages));
        }

        return ModuleManifest::fromArray($module['trix']);
    }

    /** 读取 JSON 对象并提供稳定错误。 */
    private function readJsonFile(string $path): array
    {
        try {
            $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException("Invalid JSON manifest: {$path}", previous: $exception);
        }
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new InvalidArgumentException("Invalid JSON manifest: {$path} must contain an object.");
        }

        return $decoded;
    }
}
