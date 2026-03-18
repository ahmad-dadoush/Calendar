<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Support\DatabaseWebTestCase;

final class CalendarControllerTest extends DatabaseWebTestCase
{
    public function testCalendarPageIsAccessibleForAuthenticatedUser(): void
    {
        $this->createAndLoginUser('calendar-tester@example.com');
        $this->client->request('GET', '/calendar');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('th', 'Datum');
        self::assertSelectorExists('[data-controller="calendar"]');
    }

    public function testCalendarPageRedirectsToLoginWhenAnonymous(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/calendar');

        self::assertResponseRedirects('/login');
    }
}
