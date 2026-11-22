<?php

//
// Tests de l'abonné de gestion des langues.
//
namespace App\Tests\EventSubscriber;

use PHPUnit\Framework\TestCase;
use App\EventSubscriber\LocaleSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class LocaleSubscriberTest extends TestCase
{
    //
    // Création d'un événement de requête.
    //
    private function createEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    //
    // Création d'une requête avec une session simulée.
    //
    /**
     * @param array<string, string> $query
     * @param array<string, string> $server
     */
    private function createRequest(array $query = [], array $server = []): Request
    {
        $request = new Request($query, [], [], [], [], $server);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    //
    // Événements écoutés par l'abonné.
    //
    public function testSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::REQUEST => ["onKernelRequest", 20]],
            LocaleSubscriber::getSubscribedEvents()
        );
    }

    //
    // Langue demandée explicitement dans la requête.
    //
    public function testRequestLocale(): void
    {
        $request = $this->createRequest(["_locale" => "fr"]);

        (new LocaleSubscriber())->onKernelRequest($this->createEvent($request));

        self::assertSame("fr", $request->getLocale());
        self::assertSame("fr", $request->getSession()->get("_locale"));
    }

    //
    // Langue déterminée par le navigateur.
    //
    public function testBrowserLocale(): void
    {
        $request = $this->createRequest([], ["HTTP_ACCEPT_LANGUAGE" => "fr-FR,fr;q=0.9"]);

        (new LocaleSubscriber())->onKernelRequest($this->createEvent($request));

        self::assertSame("fr", $request->getLocale());
        self::assertSame("fr", $request->getSession()->get("_locale"));
    }

    //
    // Langue déjà enregistrée dans la session.
    //
    public function testSessionLocale(): void
    {
        $request = $this->createRequest([], ["HTTP_ACCEPT_LANGUAGE" => "en-US"]);
        $request->getSession()->set("_locale", "fr");

        (new LocaleSubscriber())->onKernelRequest($this->createEvent($request));

        self::assertSame("fr", $request->getLocale());
    }

    //
    // Langue d'une requête sans état (sans session).
    //
    public function testStatelessLocale(): void
    {
        // Langue déterminée par le navigateur.
        $request = new Request([], [], ["_stateless" => true], [], [], ["HTTP_ACCEPT_LANGUAGE" => "fr-FR"]);

        (new LocaleSubscriber())->onKernelRequest($this->createEvent($request));

        self::assertSame("fr", $request->getLocale());
        self::assertFalse($request->hasSession());

        // Langue demandée explicitement dans la requête.
        $request = new Request(
            ["_locale" => "en"],
            [],
            ["_stateless" => true],
            [],
            [],
            ["HTTP_ACCEPT_LANGUAGE" => "fr-FR"]
        );

        (new LocaleSubscriber())->onKernelRequest($this->createEvent($request));

        self::assertSame("en", $request->getLocale());
    }
}
