<?php

/**
 * (c) Evis Bregu <evis.bregu@gmail.com>.
 */

namespace App\Tests\Security\Encryption;

use App\Security\Encryption\EncryptionService;
use App\Test\ContainerDependableTestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EncryptionServiceTest extends WebTestCase
{
    public function testEncryptDecrypt()
    {
        self::bootKernel();

        /**
         * @var EncryptionService
         */
        $encryptionService = self::$container->get('App\Security\Encryption\EncryptionService');
        $toEncrypt = 'TESTING ENCRYPTION';
        $encrypted = $encryptionService->encrypt($toEncrypt);
        $decrypted = $encryptionService->decrypt($encrypted);

        $this->assertSame($toEncrypt, $decrypted);
        $this->assertNotEmpty($encrypted);
        $this->assertNotEmpty($decrypted);

        $encrypted = 'CHANGED VALUE';
        $decrypted = $encryptionService->decrypt($encrypted);
        $this->assertEmpty($decrypted);
    }
}
