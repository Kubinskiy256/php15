<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\DishRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        ClientRepository $clientRepository,
        DishRepository $dishRepository, 
        OrderRepository $orderRepository
    ): Response {
        return $this->render('dashboard/index.html.twig', [
            'clients_count' => $clientRepository->count([]),
            'dishes_count' => $dishRepository->count([]),
            'orders_count' => $orderRepository->count([]),
        ]);
    }
}