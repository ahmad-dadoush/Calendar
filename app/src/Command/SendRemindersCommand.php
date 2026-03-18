<?php

namespace App\Command;

use App\Repository\CalendarItemRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:send-reminders',
    description: 'Sends reminder emails for calendar items due today.',
)]
class SendRemindersCommand extends Command
{
    public function __construct(
        private readonly CalendarItemRepository $calendarItemRepository,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $items = $this->calendarItemRepository->findDueForReminderToday();

        if (empty($items)) {
            $io->info('Keine Erinnerungen für heute.');
            return Command::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $email = (new TemplatedEmail())
                    ->from(new Address('no-reply@microlab.local', 'Micro Lab Reminder'))
                    ->to(new Address($item->getUser()->getEmail()))
                    ->subject('Erinnerung: ' . $item->getDescription() . ' am ' . $item->getDate()->format('d.m.Y'))
                    ->htmlTemplate('email/reminder.html.twig')
                    ->context(['item' => $item]);

                $this->mailer->send($email);
                ++$sent;
                $io->writeln(sprintf('  ✓ Gesendet an %s (Termin: %s)', $item->getUser()->getEmail(), $item->getDate()->format('d.m.Y')));
            } catch (\Throwable $e) {
                ++$failed;
                $io->error(sprintf('Fehler beim Senden an %s: %s', $item->getUser()->getEmail(), $e->getMessage()));
            }
        }

        $io->success(sprintf('%d Erinnerung(en) gesendet, %d fehlgeschlagen.', $sent, $failed));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
