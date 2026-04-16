<?php

namespace App\Service;

use App\Repository\CalendarItemRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ReminderService
{
    public function __construct(
        private readonly CalendarItemRepository $calendarItemRepository,
        private readonly MailerInterface $mailer,
        #[Autowire('%env(string:REMINDER_FROM_EMAIL)%')]
        private readonly string $reminderFromEmail,
        #[Autowire('%env(string:REMINDER_FROM_NAME)%')]
        private readonly string $reminderFromName,
    ) {}

    /**
     * Sends reminder emails for calendar items due today.
     *
     * @return array{sent: int, failed: int, sentEmails: string[], failedEmails: string[]}
     */
    public function sendReminders(): array
    {
        $items = $this->calendarItemRepository->findDueForReminderToday();

        $sent = 0;
        $failed = 0;
        $sentEmails = [];
        $failedEmails = [];

        foreach ($items as $item) {
            try {
                $email = (new TemplatedEmail())
                    ->from(new Address($this->reminderFromEmail, $this->reminderFromName))
                    ->to(new Address($item->getUser()->getEmail()))
                    ->subject('Erinnerung: ' . $item->getDescription() . ' am ' . $item->getDate()->format('d.m.Y'))
                    ->htmlTemplate('email/reminder.html.twig')
                    ->context(['item' => $item]);

                $this->mailer->send($email);
                ++$sent;
                $sentEmails[] = $item->getUser()->getEmail();
            } catch (\Throwable $e) {
                ++$failed;
                $failedEmails[] = $item->getUser()->getEmail();
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'sentEmails' => $sentEmails,
            'failedEmails' => $failedEmails,
        ];
    }
}
