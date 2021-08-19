<?php

/**
 * (c) Evis Bregu <evis.bregu@gmail.com>.
 */

namespace App\Tests\Entity;

use App\Entity\User;
use App\Test\ContainerDependableTestCase;

class UserTest extends ContainerDependableTestCase
{
    public function testUser()
    {
        $user = new User();
        $user->setEmail('bar@foo.com')
            ->setMobile('1234567')
            ->setPlainPassword('barfoo')
            ->setRoles(['ROLE_USER']);

        $this->_container->get('doctrine')->getManager()->persist($user);
        $this->_container->get('doctrine')->getManager()->flush();

        $encoder = $this->_container->get('security.password_encoder');
        $this->assertTrue($encoder->isPasswordValid($user, 'barfoo'));

        $this->assertNotNull($user->getPlainPassword());
        $user->eraseCredentials();
        $this->assertNull($user->getPlainPassword());

        $this->assertInternalType('int', $user->getId());
        $this->assertSame('bar@foo.com', $user->getEmail());
        $this->assertSame('bar@foo.com', $user->getUsername());
        $this->assertSame(['ROLE_USER'], $user->getRoles());

        //Even we dont set ROLE_USER for the given user we will add it to the roles array
        $user->setRoles(['ROLE_ADMIN']);
        $this->assertTrue(in_array('ROLE_USER', $user->getRoles(), true));
        $this->assertTrue(in_array('ROLE_ADMIN', $user->getRoles(), true));
    }
}
