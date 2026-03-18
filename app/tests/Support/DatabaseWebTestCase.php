<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\CalendarItem;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->purgeDatabase();
    }

    protected function createAndLoginUser(?string $email = null): User
    {
        $uniqueEmail = $email ?? sprintf('user_%s@example.com', bin2hex(random_bytes(6)));

        $user = (new User())
            ->setEmail($uniqueEmail)
            ->setPassword('test-password');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        return $user;
    }

    protected function createCalendarItem(
        User $user,
        string $date,
        string $description,
        int $reminderDays
    ): CalendarItem {
        $item = (new CalendarItem())
            ->setUser($user)
            ->setDate(new DateTimeImmutable($date))
            ->setDescription($description)
            ->setReminderDays($reminderDays);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }

    private function purgeDatabase(): void
    {
        $connection = $this->entityManager->getConnection();

        $connection->executeStatement('TRUNCATE TABLE calendar_item, app_user RESTART IDENTITY CASCADE');
    }
}
