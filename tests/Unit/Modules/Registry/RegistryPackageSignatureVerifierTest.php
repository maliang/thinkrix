<?php

namespace Thinkrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Registry\RegistryPackageSignatureVerifier;

class RegistryPackageSignatureVerifierTest extends TestCase
{
    public function testVerifiesChecksumSignatureWithPublicKey(): void
    {
        $payload = 'sha256:' . hash('sha256', 'package');
        $secret = 'registry-secret';
        $signature = hash_hmac('sha256', $payload, $secret, true);

        $result = (new RegistryPackageSignatureVerifier())->verify($payload, 'hmac-sha256:' . base64_encode($signature), $secret);

        self::assertTrue($result['verified']);
        self::assertSame('signature_verified', $result['reason']);
    }

    public function testRejectsSignatureForDifferentPayload(): void
    {
        $secret = 'registry-secret';
        $signature = hash_hmac('sha256', 'sha256:trusted', $secret, true);

        $result = (new RegistryPackageSignatureVerifier())->verify('sha256:tampered', 'hmac-sha256:' . base64_encode($signature), $secret);

        self::assertFalse($result['verified']);
        self::assertSame('signature_invalid', $result['reason']);
    }

}
