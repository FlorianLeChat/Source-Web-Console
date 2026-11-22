<?php

//
// Faux service d'envoi de courriels utilisé dans l'environnement de test.
//
namespace App\Tests\Mock;

use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportException;

final class FakeMailer implements MailerInterface
{
    // Indique si l'envoi doit échouer.
    public static bool $fail = false;

    // Liste des messages envoyés.
    /** @var RawMessage[] */
    public static array $messages = [];

    //
    // Réinitialisation de l'état partagé entre les tests.
    //
    public static function reset(): void
    {
        self::$fail = false;
        self::$messages = [];
    }

    //
    // Simulation de l'envoi d'un message.
    //
    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        if (self::$fail)
        {
            throw new TransportException("Connection could not be established with host \"smtp.example.com\".");
        }

        self::$messages[] = $message;
    }
}
