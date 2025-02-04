<?php

namespace Tests\Wallabag\CoreBundle\Event\Listener;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Wallabag\CoreBundle\Event\Listener\AuthenticationFailureListener;

class AuthenticationFailureListenerTest extends TestCase
{
    private $requestStack;
    private $logHandler;
    private $listener;
    private $dispatcher;

    protected function setUp(): void
    {
        $request = Request::create('/');
        $request->request->set('_username', 'admin');

        $this->requestStack = new RequestStack();
        $this->requestStack->push($request);

        $this->logHandler = new TestHandler();
        $logger = new Logger('test', [$this->logHandler]);

        $this->listener = new AuthenticationFailureListener(
            $this->requestStack,
            $logger
        );

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->listener);
    }

    public function testOnAuthenticationFailure()
    {
        $token = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exception = $this->getMockBuilder(AuthenticationException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = new AuthenticationFailureEvent(
            $token,
            $exception
        );

        $this->dispatcher->dispatch(
            $event,
            AuthenticationEvents::AUTHENTICATION_FAILURE
        );

        $records = $this->logHandler->getRecords();

        $this->assertCount(1, $records);
        $this->assertSame('Authentication failure for user "admin", from IP "127.0.0.1", with UA: "Symfony".', $records[0]['message']);
    }
}
