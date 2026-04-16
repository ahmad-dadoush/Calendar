<?php

namespace App\Command;

use App\Service\ReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-reminders',
    description: 'Sends reminder emails for calendar items due today.',
)]
class SendRemindersCommand extends Command
{
    public function __construct(
        private readonly ReminderService $reminderService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->reminderService->sendReminders();

        if ($result['sent'] === 0 && $result['failed'] === 0) {
            $io->info('Keine Erinnerungen für heute.');
            return Command::SUCCESS;
        }

        foreach ($result['sentEmails'] as $email) {
            $io->writeln(sprintf('  ✓ Gesendet an %s', $email));
        }

        foreach ($result['failedEmails'] as $email) {
            $io->error(sprintf('Fehler beim Senden an %s', $email));
        }

        $io->success(sprintf('%d Erinnerung(en) gesendet, %d fehlgeschlagen.', $result['sent'], $result['failed']));

        return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
