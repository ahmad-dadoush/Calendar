<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\SendRemindersCommand;
use App\Entity\CalendarItem;
use App\Entity\User;
use App\Repository\CalendarItemRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;

final class SendRemindersCommandTest extends TestCase
{
    public function testExecuteSendsReminderEmailForDueItem(): void
    {
        $item = $this->createCalendarItem(
            email: 'recipient@example.com',
            date: '2026-04-10',
            description: 'Zahnarzt',
            reminderDays: CalendarItem::REMINDER_TWO_DAYS
        );

        $repository = $this->createMock(CalendarItemRepository::class);
        $repository
            ->expects(self::once())
            ->method('findDueForReminderToday')
            ->willReturn([$item]);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('recipient@example.com', $email->getTo()[0]->getAddress());
                self::assertStringContainsString('Zahnarzt', (string) $email->getSubject());
                self::assertSame('email/reminder.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        $command = new SendRemindersCommand($repository, $mailer);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('1 Erinnerung(en) gesendet, 0 fehlgeschlagen.', $tester->getDisplay());
    }

    public function testExecutePrintsInfoWhenNoReminderIsDue(): void
    {
        $repository = $this->createMock(CalendarItemRepository::class);
        $repository
            ->expects(self::once())
            ->method('findDueForReminderToday')
            ->willReturn([]);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $command = new SendRemindersCommand($repository, $mailer);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Keine Erinnerungen für heute.', $tester->getDisplay());
    }

    private function createCalendarItem(string $email, string $date, string $description, int $reminderDays): CalendarItem
    {
        $user = (new User())
            ->setEmail($email)
            ->setPassword('dummy');

        return (new CalendarItem())
            ->setUser($user)
            ->setDate(new DateTimeImmutable($date))
            ->setDescription($description)
            ->setReminderDays($reminderDays);
    }
}
