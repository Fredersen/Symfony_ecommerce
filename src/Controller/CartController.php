<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{

    private Session $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    #[Route('/mon-panier', name: 'app_cart')]
    public function index(ProductRepository $productRepository): Response
    {
        $cart = $this->session->get('cart');

        $cartComplete = [];

        if($cart) {
            foreach ($cart as $id => $quantity) {
                $cartComplete[] = [
                    'product' => $productRepository->find($id),
                    'quantity' => $quantity
                ];
            }
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cartComplete,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_add_to_cart')]
    public function add($id): Response
    {
        $cart = $this->session->get('cart', []);

        if(!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }

        $this->session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove', name: 'app_remove_cart')]
    public function remove(SessionInterface $session): Response
    {
        $this->session->remove('cart');

        return $this->redirectToRoute('app_products');
    }

    #[Route('/cart/delete/{id}', name: 'app_delete_cart')]
    public function delete(SessionInterface $session, $id): Response
    {
        $cart = $this->session->get('cart', []);
        unset($cart[$id]);
        $this->session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/decrease/{id}', name: 'app_decrease_cart')]
    public function decrease(SessionInterface $session, $id): Response
    {
        $cart = $this->session->get('cart', []);

        if($cart[$id] > 1) {
            $cart[$id]--;
        } else {
            unset($cart[$id]);
        }
        $this->session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }
}
