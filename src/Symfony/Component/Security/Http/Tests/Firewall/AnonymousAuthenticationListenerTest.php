<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Tests\Firewall;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;

class AnonymousAuthenticationListenerTest extends TestCase
{
    public function testHandleWithTokenStorageHavingAToken()
    {
        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();
        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class)->getMock())
        ;
        $tokenStorage
            ->expects($this->never())
            ->method('setToken')
        ;

        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();
        $authenticationManager
            ->expects($this->never())
            ->method('authenticate')
        ;

        $listener = new AnonymousAuthenticationListener($tokenStorage, 'TheSecret', null, $authenticationManager);
        $listener(new RequestEvent($this->createMock(HttpKernelInterface::class), new Request(), HttpKernelInterface::MASTER_REQUEST));
    }

    public function testHandleWithTokenStorageHavingNoToken()
    {
        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();
        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn(null)
        ;

        $anonymousToken = new AnonymousToken('TheSecret', 'anon.', []);

        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();
        $authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($this->callback(function ($token) {
                return 'TheSecret' === $token->getSecret();
            }))
            ->willReturn($anonymousToken)
        ;

        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($anonymousToken)
        ;

        $listener = new AnonymousAuthenticationListener($tokenStorage, 'TheSecret', null, $authenticationManager);
        $listener(new RequestEvent($this->createMock(HttpKernelInterface::class), new Request(), HttpKernelInterface::MASTER_REQUEST));
    }

    public function testHandledEventIsLogged()
    {
        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();
        $logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)->getMock();
        $logger->expects($this->once())
            ->method('info')
            ->with('Populated the TokenStorage with an anonymous Token.')
        ;

        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();

        $listener = new AnonymousAuthenticationListener($tokenStorage, 'TheSecret', $logger, $authenticationManager);
        $listener(new RequestEvent($this->createMock(HttpKernelInterface::class), new Request(), HttpKernelInterface::MASTER_REQUEST));
    }
}
