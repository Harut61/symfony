<?php

/**
 * (c) Evis Bregu <evis.bregu@gmail.com>.
 */

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\JwtTokenAuthenticator;
use App\Test\ContainerDependableTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class JwtTokenAuthenticatorTest extends ContainerDependableTestCase
{
    /**
     * @throws JWTEncodeFailureException
     */
    public function testJwtAuthorization()
    {
        $jwtAuthenticator = new JwtTokenAuthenticator(
            $this->_container->get('lexik_jwt_authentication.encoder.lcobucci'),
            $this->_container->get('app.user_repository'),
            $this->_container->get('App\Api\ResponseFactory')
        );

        $user = new User();
        $user->setEmail('bar@foo.com')
            ->setMobile('1234567')
            ->setPlainPassword('barfoo')
            ->setRoles(['ROLE_USER']);

        $this->_container->get('doctrine')->getManager()->persist($user);
        $this->_container->get('doctrine')->getManager()->flush();

        $request = new Request();

        // Emulate start() method call. This method is called when authentication info is missing
        // from a request that requires it
        $noAuthHeaderResponse = $jwtAuthenticator->start($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $noAuthHeaderResponse);
        $this->assertContains('Missing credentials', $noAuthHeaderResponse->getContent());

        $token = $this->_container->get('lexik_jwt_authentication.encoder.lcobucci')
            ->encode(
                [
                    'username' => $user->getUsername(),
                    'exp' => time() + 3600, // 1 hour expiration
                ]
            );

        $request->headers->set('Authorization', 'Bearer '.$token);

        // Get credentials (authorization token) from request headers
        $credentials = $jwtAuthenticator->getCredentials($request);
        // Assert that credentials are the same as the token that we sent in the request headers
        $this->assertSame($token, $credentials);

        //Real user with good credentials
        $authUser = $jwtAuthenticator->getUser(
            $credentials,
            $this->createMock(EntityUserProvider::class)
        );

        $this->assertNotNull($authUser);
        $this->assertInstanceOf('App\Entity\User', $authUser);
        $this->assertTrue($jwtAuthenticator->checkCredentials($credentials, $user));

        $authFailedResponse = $jwtAuthenticator->onAuthenticationFailure(
            new Request(),
            new AuthenticationException('Invalid Token')
        );
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $authFailedResponse);
        $this->assertSame(401, $authFailedResponse->getStatusCode());

        $this->assertFalse($jwtAuthenticator->supportsRememberMe());

        //Emulate bad credentials. Keep at the end of test to allow other assertions to execute
        $badCredentials = 'DummyText';
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $jwtAuthenticator->getUser($badCredentials, $this->_container->get('security.user.provider.concrete.our_users'));
    }
}
