<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReminderController extends AbstractController
{
    #[Route('/reminder', name: 'app_reminder')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Only authenticated users can access reminder page.');
        }

        return $this->render('reminder/index.html.twig');
    }
}