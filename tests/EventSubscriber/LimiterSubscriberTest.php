<?php

//
// Tests de l'abonné de limitation du nombre de requêtes.
//
namespace App\Tests\EventSubscriber;

use PHPUnit\Framework\TestCase;
use App\EventSubscriber\LimiterSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class LimiterSubscriberTest extends TestCase
{
    // Abonné testé.
    private LimiterSubscriber $subscriber;

    //
    // Création de l'abonné avec une limite d'une seule requête.
    //
    protected function setUp(): void
    {
        $factory = new RateLimiterFactory(
            ["id" => "api", "policy" => "fixed_window", "limit" => 1, "interval" => "1 minute"],
            new InMemoryStorage()
        );

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method("trans")->willReturn("Too many requests");

        $this->subscriber = new LimiterSubscriber($factory, $translator);
    }

    //
    // Envoi d'une requête vers une route donnée.
    //
    private function dispatch(string $uri): RequestEvent
    {
        $event = new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($uri),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->subscriber->onKernelRequest($event);

        return $event;
    }

    //
    // Événements écoutés par l'abonné.
    //
    public function testSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::REQUEST => ["onKernelRequest", 5]],
            LimiterSubscriber::getSubscribedEvents()
        );
    }

    //
    // Limitation des requêtes vers l'API.
    //
    public function testApiRateLimit(): void
    {
        // Première requête (acceptée).
        self::assertNull($this->dispatch("/api/server/monitor")->getResponse());

        // Deuxième requête (refusée).
        $response = $this->dispatch("/api/server/monitor")->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        self::assertSame("Too many requests", $response->getContent());

        // Requête vers une page classique (jamais limitée).
        self::assertNull($this->dispatch("/dashboard")->getResponse());
    }
}
