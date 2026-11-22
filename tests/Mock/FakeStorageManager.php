<?php

//
// Faux service de gestion des serveurs de stockage utilisé dans l'environnement de test.
//  Note : seule l'ouverture de la connexion est simulée, la lecture et l'écriture
//   des fichiers utilisent le code réel du service.
//
namespace App\Tests\Mock;

use App\Entity\Storage;
use App\Service\StorageManager;

final class FakeStorageManager extends StorageManager
{
    // Flux renvoyé lors de l'ouverture d'une connexion.
    public static mixed $stream = null;

    //
    // Réinitialisation de l'état partagé entre les tests.
    //
    public static function reset(): void
    {
        self::$stream = null;
    }

    //
    // Simulation de l'ouverture d'une connexion.
    //
    public function openConnection(Storage $storage): mixed
    {
        return self::$stream;
    }
}
