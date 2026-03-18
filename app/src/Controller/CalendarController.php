<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CalendarItemType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'app_calendar')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Only authenticated users can access calendar items.');
        }

        $calendarForm = $this->createForm(CalendarItemType::class);

        return $this->render('calendar/index.html.twig', [
            'calendarForm' => $calendarForm->createView(),
        ]);
    }
}