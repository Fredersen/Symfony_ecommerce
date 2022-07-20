<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Repository\HeaderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(SessionInterface $session, ProductRepository $productRepository, HeaderRepository $headerRepository): Response
    {
        $products = $productRepository->findBy(['onHomepage'  => 1]);
        $headers = $headerRepository->findAll();
        return $this->render('home/index.html.twig', [
            'products' => $products,
            'headers' => $headers
        ]);
    }
}
