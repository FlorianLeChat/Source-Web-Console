<?php

//
// Tests de l'abonné aux événements du panneau d'administration.
//
namespace App\Tests\EventSubscriber;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use App\EventSubscriber\EasyAdminSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class EasyAdminSubscriberTest extends TestCase
{
    //
    // Événements écoutés par l'abonné.
    //
    public function testSubscribedEvents(): void
    {
        self::assertSame([
            BeforeEntityUpdatedEvent::class => ["hashUserPassword"],
            BeforeEntityPersistedEvent::class => ["hashUserPassword"],
        ], EasyAdminSubscriber::getSubscribedEvents());
    }

    //
    // Hachage du mot de passe avant la création ou la mise à jour.
    //
    public function testHashUserPassword(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->exactly(2))
            ->method("hashPassword")
            ->willReturnCallback(
                fn (User $user, string $password) => sprintf("hashed_%s_%s", $user->getPassword(), $password)
            );

        $subscriber = new EasyAdminSubscriber($hasher);

        // Création d'un utilisateur.
        $user = (new User())->setPassword("florian4016");
        $subscriber->hashUserPassword(new BeforeEntityPersistedEvent($user));

        self::assertSame("hashed_florian4016_florian4016", $user->getPassword());

        // Mise à jour d'un utilisateur.
        $user->setPassword("florian4017");
        $subscriber->hashUserPassword(new BeforeEntityUpdatedEvent($user));

        self::assertSame("hashed_florian4017_florian4017", $user->getPassword());
    }
}
