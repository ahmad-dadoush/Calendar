<?php

namespace App\Controller\Api;

use App\Entity\CalendarItem;
use App\Entity\User;
use App\Repository\CalendarItemRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/calendar-items', name: 'api_calendar_item_')]
final class CalendarItemApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CalendarItemRepository $calendarItemRepository): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $items = $calendarItemRepository->findBy(['user' => $user], ['date' => 'ASC']);

        return $this->json([
            'items' => array_map(fn (CalendarItem $item): array => $this->serializeItem($item), $items),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getAuthenticatedUser();
        $payload = $this->decodeJsonPayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $calendarItem = new CalendarItem();
        $calendarItem->setUser($user);

        $payloadErrors = $this->applyPayload($calendarItem, $payload);
        if ([] !== $payloadErrors) {
            return $this->json(['errors' => $payloadErrors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $violations = $validator->validate($calendarItem);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->persist($calendarItem);
        $entityManager->flush();

        return $this->json($this->serializeItem($calendarItem), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, CalendarItemRepository $calendarItemRepository): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $calendarItem = $calendarItemRepository->findOneBy(['id' => $id, 'user' => $user]);

        if (null === $calendarItem) {
            return $this->json(['message' => 'Calendar item not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeItem($calendarItem));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        CalendarItemRepository $calendarItemRepository,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getAuthenticatedUser();
        $calendarItem = $calendarItemRepository->findOneBy(['id' => $id, 'user' => $user]);

        if (null === $calendarItem) {
            return $this->json(['message' => 'Calendar item not found.'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->decodeJsonPayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $payloadErrors = $this->applyPayload($calendarItem, $payload);
        if ([] !== $payloadErrors) {
            return $this->json(['errors' => $payloadErrors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $violations = $validator->validate($calendarItem);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->flush();

        return $this->json($this->serializeItem($calendarItem));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        CalendarItemRepository $calendarItemRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getAuthenticatedUser();
        $calendarItem = $calendarItemRepository->findOneBy(['id' => $id, 'user' => $user]);

        if (null === $calendarItem) {
            return $this->json(['message' => 'Calendar item not found.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($calendarItem);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        return $user;
    }

    private function decodeJsonPayload(Request $request): array|JsonResponse
    {
        $content = trim($request->getContent());

        if ('' === $content) {
            return [];
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->json(['message' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($data)) {
            return $this->json(['message' => 'JSON payload must be an object.'], Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, list<string>>
     */
    private function applyPayload(CalendarItem $calendarItem, array $payload): array
    {
        $errors = [];

        if (array_key_exists('description', $payload)) {
            $description = $payload['description'];
            $calendarItem->setDescription(is_string($description) ? $description : null);
        }

        if (array_key_exists('reminderDays', $payload)) {
            $reminderDays = $payload['reminderDays'];
            $calendarItem->setReminderDays(is_numeric($reminderDays) ? (int) $reminderDays : null);
        }

        if (array_key_exists('date', $payload)) {
            $dateInput = $payload['date'];

            if (!is_string($dateInput) || '' === trim($dateInput)) {
                $calendarItem->setDate(null);
            } else {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateInput);

                if (false === $date) {
                    $errors['date'][] = 'Date must be in format YYYY-MM-DD.';
                } else {
                    $calendarItem->setDate($date);
                }
            }
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeItem(CalendarItem $calendarItem): array
    {
        return [
            'id' => $calendarItem->getId(),
            'date' => $calendarItem->getDate()?->format('Y-m-d'),
            'formattedDate' => $calendarItem->getFormattedDate(),
            'description' => $calendarItem->getDescription(),
            'reminderDays' => $calendarItem->getReminderDays(),
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function formatViolations(iterable $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            if (!$violation instanceof ConstraintViolationInterface) {
                continue;
            }

            $field = '' === $violation->getPropertyPath() ? 'general' : $violation->getPropertyPath();
            $errors[$field][] = $violation->getMessage();
        }

        return $errors;
    }
}
