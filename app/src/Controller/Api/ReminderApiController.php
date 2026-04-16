<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ReminderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reminders', name: 'api_reminders_')]
final class ReminderApiController extends AbstractController
{
    public function __construct(
        private readonly ReminderService $reminderService,
    ) {}

    #[Route('/send-all', name: 'send_all', methods: ['POST'])]
    public function sendAll(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();

        $result = $this->reminderService->sendReminders();

        return $this->json([
            'message' => sprintf('%d Erinnerung(en) gesendet, %d fehlgeschlagen.', $result['sent'], $result['failed']),
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'sentEmails' => $result['sentEmails'],
            'failedEmails' => $result['failedEmails'],
        ]);
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        return $user;
    }
}
