<?php

namespace Thinkrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Registry\RegistryPackageDownloader;

class RegistryPackageDownloaderTest extends TestCase
{
    public function testDownloadsPackageToCacheWhenChecksumMatches(): void
    {
        $content = 'adapter package';
        $cachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-registry-test-' . uniqid('', true);
        $downloader = new RegistryPackageDownloader($cachePath, fn (string $url): string => $content);

        $result = $downloader->download([
            'language' => 'php',
            'framework' => 'thinkphp',
            'package_url' => 'https://registry.example/packages/official.cms.zip',
            'checksum' => 'sha256:' . hash('sha256', $content),
        ], 'official.cms', '1.0.0');

        self::assertTrue($result['downloaded']);
        self::assertFileExists($result['path']);
        self::assertSame($content, file_get_contents($result['path']));
        self::assertStringContainsString('official.cms-1.0.0-php-thinkphp', basename($result['path']));

        $second = $downloader->download([
            'language' => 'php',
            'framework' => 'thinkphp',
            'package_url' => 'https://registry.example/packages/official.cms.zip',
            'checksum' => 'sha256:' . hash('sha256', $content),
        ], 'official.cms', '1.0.0');
        self::assertTrue($second['downloaded']);
        self::assertNotSame($result['path'], $second['path']);
    }

    public function testRejectsPackageWhenChecksumDoesNotMatch(): void
    {
        $cachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-registry-test-' . uniqid('', true);
        $downloader = new RegistryPackageDownloader($cachePath, fn (string $url): string => 'tampered package');

        $result = $downloader->download([
            'language' => 'php',
            'framework' => 'thinkphp',
            'package_url' => 'https://registry.example/packages/official.cms.zip',
            'checksum' => 'sha256:' . hash('sha256', 'expected package'),
        ], 'official.cms', '1.0.0');

        self::assertFalse($result['downloaded']);
        self::assertSame('checksum_mismatch', $result['reason']);
        self::assertNull($result['path']);
    }

    public function testDownloadsPackageWhenSignatureMatchesChecksum(): void
    {
        $content = 'adapter package';
        $checksum = 'sha256:' . hash('sha256', $content);
        $secret = 'registry-secret';
        $signature = 'hmac-sha256:' . base64_encode(hash_hmac('sha256', $checksum, $secret, true));
        $cachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-registry-test-' . uniqid('', true);
        $downloader = new RegistryPackageDownloader($cachePath, fn (string $url): string => $content, $secret);

        $result = $downloader->download([
            'language' => 'php',
            'framework' => 'thinkphp',
            'package_url' => 'https://registry.example/packages/official.cms.zip',
            'checksum' => $checksum,
            'signature' => $signature,
        ], 'official.cms', '1.0.0');

        self::assertTrue($result['downloaded']);
        self::assertSame('signature_verified', $result['signature_reason']);
    }

    public function testRejectsPackageWhenSignatureDoesNotMatchChecksum(): void
    {
        $content = 'adapter package';
        $checksum = 'sha256:' . hash('sha256', $content);
        $signature = 'hmac-sha256:' . base64_encode(hash_hmac('sha256', 'sha256:other', 'registry-secret', true));
        $cachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-registry-test-' . uniqid('', true);
        $downloader = new RegistryPackageDownloader($cachePath, fn (string $url): string => $content, 'registry-secret');

        $result = $downloader->download([
            'language' => 'php',
            'framework' => 'thinkphp',
            'package_url' => 'https://registry.example/packages/official.cms.zip',
            'checksum' => $checksum,
            'signature' => $signature,
        ], 'official.cms', '1.0.0');

        self::assertFalse($result['downloaded']);
        self::assertSame('signature_invalid', $result['reason']);
        self::assertNull($result['path']);
    }
}
