<?php

/**
 * (c) Evis Bregu <evis.bregu@gmail.com>.
 */

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\LoginFormAuthenticator;
use App\Test\ContainerDependableTestCase;
use Symfony\Component\HttpFoundation\Request;

class LoginFormAuthenticatorTest extends ContainerDependableTestCase
{
    /**
     * @throws \Exception
     */
    public function testLoginAuthenticator()
    {
        $loginAuthenticator = new LoginFormAuthenticator(
            $this->_container->get('form.factory'),
            $this->_container->get('app.user_repository'),
            $this->_container->get('router'),
            $this->_container->get('security.password_encoder')
        );

        $user = new User();
        $user->setEmail('bar@foo.com')
            ->setPlainPassword('barfoo')
            ->setMobile('355661234567')
            ->setRoles(['ROLE_USER']);

        $this->_container->get('doctrine')->getManager()->persist($user);
        $this->_container->get('doctrine')->getManager()->flush();

        $post = ['login_form' => ['_username' => 'bar@foo.com', '_password' => 'barfoo']];
        $request = new Request([], $post);
        $request->setSession($this->_container->get('session'));
        $request->server->set('REQUEST_URI', '/login');
        $request->setMethod('POST');

        $credentials = $loginAuthenticator->getCredentials($request);
        $this->assertInternalType('array', $credentials);
        $this->assertArrayHasKey('_username', $credentials);
        $this->assertArrayHasKey('_password', $credentials);

        $authUser = $loginAuthenticator->getUser(
            $credentials,
            $this->_container->get('security.user.provider.concrete.our_users')
        );

        $this->assertNotNull($authUser);
        $this->assertInstanceOf('App\Entity\User', $authUser);

        $this->assertTrue($loginAuthenticator->checkCredentials($credentials, $authUser));

        $anotherUser = new User();
        $anotherUser->setEmail('admin@foo.com')
            ->setPlainPassword('adminfoo')
            ->setMobile('355661234568')
            ->setRoles(['ROLE_ADMIN']);

        $this->assertFalse($loginAuthenticator->checkCredentials($credentials, $anotherUser));

        $nonLoginRequest = new Request();
        $credentials = $loginAuthenticator->getCredentials($nonLoginRequest);

        $this->assertNull($credentials);
    }
}
