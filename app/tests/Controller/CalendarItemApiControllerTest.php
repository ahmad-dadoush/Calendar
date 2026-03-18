<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\CalendarItem;
use App\Entity\User;
use App\Repository\CalendarItemRepository;
use App\Tests\Support\DatabaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CalendarItemApiControllerTest extends DatabaseWebTestCase
{
    public function testIndexReturnsOnlyCurrentUserItems(): void
    {
        $currentUser = $this->createAndLoginUser('current-user@example.com');
        $otherUser = (new User())
            ->setEmail('other-user@example.com')
            ->setPassword('test-password');

        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $this->createCalendarItem($currentUser, '2026-04-10', 'Current users item', CalendarItem::REMINDER_ONE_DAY);
        $this->createCalendarItem($otherUser, '2026-04-11', 'Other users item', CalendarItem::REMINDER_TWO_DAYS);

        $this->client->request('GET', '/api/calendar-items');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('items', $payload);
        self::assertCount(1, $payload['items']);
        self::assertSame('Current users item', $payload['items'][0]['description']);
    }

    public function testCreatePersistsCalendarItem(): void
    {
        $user = $this->createAndLoginUser('creator@example.com');

        $this->client->request(
            'POST',
            '/api/calendar-items',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: json_encode([
                'date' => '2026-05-01',
                'description' => 'Doctor appointment',
                'reminderDays' => CalendarItem::REMINDER_TWO_DAYS,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $repository = static::getContainer()->get(CalendarItemRepository::class);
        $items = $repository->findBy(['user' => $user]);

        self::assertCount(1, $items);
        self::assertSame('Doctor appointment', $items[0]->getDescription());
    }

    public function testCreateReturnsValidationErrorForInvalidDateFormat(): void
    {
        $this->createAndLoginUser('invalid-date@example.com');

        $this->client->request(
            'POST',
            '/api/calendar-items',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: json_encode([
                'date' => '05/01/2026',
                'description' => 'Wrong format date',
                'reminderDays' => CalendarItem::REMINDER_ONE_WEEK,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Date must be in format YYYY-MM-DD.', $payload['errors']['date'][0] ?? null);
    }

    public function testUpdateAndDeleteFlow(): void
    {
        $user = $this->createAndLoginUser('editor@example.com');
        $item = $this->createCalendarItem($user, '2026-06-15', 'Initial title', CalendarItem::REMINDER_ONE_DAY);

        $this->client->request(
            'PATCH',
            sprintf('/api/calendar-items/%d', $item->getId()),
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: json_encode([
                'description' => 'Updated title',
                'date' => '2026-06-20',
                'reminderDays' => CalendarItem::REMINDER_TWO_WEEKS,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->entityManager->clear();
        $repository = static::getContainer()->get(CalendarItemRepository::class);
        $updatedItem = $repository->find($item->getId());

        self::assertNotNull($updatedItem);
        self::assertSame('Updated title', $updatedItem->getDescription());
        self::assertSame('2026-06-20', $updatedItem->getDate()?->format('Y-m-d'));

        $this->client->request('DELETE', sprintf('/api/calendar-items/%d', $item->getId()));

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->entityManager->clear();
        self::assertNull($repository->find($item->getId()));
    }
}
