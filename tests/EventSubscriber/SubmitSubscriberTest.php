<?php

//
// Tests de l'abonné de vérification des soumissions (Google reCAPTCHA).
//
namespace App\Tests\EventSubscriber;

use PHPUnit\Framework\TestCase;
use App\EventSubscriber\SubmitSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

final class SubmitSubscriberTest extends TestCase
{
    //
    // Création des paramètres du service.
    //
    private function createParameters(bool $enabled): ContainerBagInterface
    {
        $parameters = $this->createStub(ContainerBag::class);
        $parameters->method("get")->willReturnMap([
            ["app.recaptcha_private_key", "secret"],
            ["app.recaptcha_enabled", $enabled ? "true" : "false"]
        ]);

        return $parameters;
    }

    //
    // Envoi d'une requête à l'abonné.
    //
    private function dispatch(SubmitSubscriber $subscriber, Request $request): RequestEvent
    {
        $event = new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber->onKernelRequest($event);

        return $event;
    }

    //
    // Création d'une réponse JSON de l'API Google reCAPTCHA.
    //
    /** @param array<string, mixed> $body */
    private function createResponse(array $body, int $status = 200): MockResponse
    {
        return new MockResponse(json_encode($body), [
            "http_code" => $status,
            "response_headers" => ["content-type" => "application/json"]
        ]);
    }

    //
    // Événements écoutés par l'abonné.
    //
    public function testSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::REQUEST => ["onKernelRequest", 10]],
            SubmitSubscriber::getSubscribedEvents()
        );
    }

    //
    // Service Google reCAPTCHA désactivé.
    //
    public function testRecaptchaDisabled(): void
    {
        $client = new MockHttpClient();
        $subscriber = new SubmitSubscriber($client, $this->createParameters(false));

        $event = $this->dispatch($subscriber, Request::create("/api/user/login", "POST"));

        self::assertNull($event->getResponse());
        self::assertSame(0, $client->getRequestsCount());
    }

    //
    // Requêtes ignorées par la vérification.
    //
    public function testIgnoredRequests(): void
    {
        $client = new MockHttpClient();
        $subscriber = new SubmitSubscriber($client, $this->createParameters(true));

        // Requête de type GET.
        $event = $this->dispatch($subscriber, Request::create("/dashboard"));

        self::assertNull($event->getResponse());

        // Requête vers le panneau d'administration.
        $request = Request::create("/admin", "POST");
        $request->attributes->set("_route", "admin_page");

        $event = $this->dispatch($subscriber, $request);

        self::assertNull($event->getResponse());
        self::assertSame(0, $client->getRequestsCount());
    }

    //
    // Vérification réussie.
    //
    public function testValidToken(): void
    {
        $client = new MockHttpClient($this->createResponse(["success" => true, "score" => 0.9]));
        $subscriber = new SubmitSubscriber($client, $this->createParameters(true));

        $event = $this->dispatch($subscriber, Request::create("/api/user/login", "POST", ["recaptcha" => "token"]));

        self::assertNull($event->getResponse());
        self::assertSame(1, $client->getRequestsCount());
    }

    //
    // Vérification échouée (score trop faible).
    //
    public function testLowScore(): void
    {
        $client = new MockHttpClient($this->createResponse(["success" => true, "score" => 0.1]));
        $subscriber = new SubmitSubscriber($client, $this->createParameters(true));

        $event = $this->dispatch($subscriber, Request::create("/api/user/login", "POST", ["recaptcha" => "token"]));

        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    //
    // Vérification échouée (jeton manquant).
    //
    public function testMissingToken(): void
    {
        $client = new MockHttpClient($this->createResponse(["success" => false, "score" => 0]));
        $subscriber = new SubmitSubscriber($client, $this->createParameters(true));

        $event = $this->dispatch($subscriber, Request::create("/api/user/login", "POST"));

        self::assertNotNull($event->getResponse());
    }

    //
    // Vérification échouée (erreur de l'API).
    //  Note : le contenu de la réponse est tout de même analysé.
    //
    public function testApiError(): void
    {
        $client = new MockHttpClient($this->createResponse(["success" => false, "score" => 0], 202));
        $subscriber = new SubmitSubscriber($client, $this->createParameters(true));

        $event = $this->dispatch($subscriber, Request::create("/api/user/login", "POST", ["recaptcha" => "token"]));

        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }
}
