<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Form\OrderType;
use App\Repository\OrderDetailsRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{

    private OrderRepository $orderRepository;

    private OrderDetailsRepository $orderDetailsRepository;

    /**
     * @param OrderRepository $orderRepository
     * @param OrderDetailsRepository $orderDetailsRepository
     */
    public function __construct(OrderRepository $orderRepository, OrderDetailsRepository $orderDetailsRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->orderDetailsRepository = $orderDetailsRepository;
    }


    #[Route('/commande', name: 'app_order')]
    public function index(Session $session, ProductRepository $productRepository, Request $request): Response
    {
        if(!$this->getUser()->getAdresses()->getValues()) {
            return $this->redirectToRoute('app_account_adress_add');
        }

        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);

        $cart = $session->get('cart');

        $cartComplete = [];

        if($cart) {
            foreach ($cart as $id => $quantity) {
                $cartComplete[] = [
                    'product' => $productRepository->find($id),
                    'quantity' => $quantity
                ];
            }
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'cart' => $cartComplete
        ]);
    }

    #[Route('/commande/recapitulatif', name: 'app_order_summary', methods: ['POST'])]
    public function add(Session $session, ProductRepository $productRepository, Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();

        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);

        $cart = $session->get('cart');

        $cartComplete = [];

        if($cart) {
            foreach ($cart as $id => $quantity) {
                $cartComplete[] = [
                    'product' => $productRepository->find($id),
                    'quantity' => $quantity
                ];
            }
        }

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $order = new Order();

            $delivery = $form->get('addresses')->getData();
            $deliveryContent = $delivery->getFirstName() . " " . $delivery->getLastName();
            if($delivery->getCompany()) {
                $deliveryContent .= "<br/>" . $delivery->getCompany();
            }

            $deliveryContent .= "<br/>" . $delivery->getAdress();
            $deliveryContent .= "<br/>" . $delivery->getPostal() . " " . $delivery->getCity();
            $deliveryContent .= "<br/>" . $delivery->getCountry();

            $order->setUser($this->getUser())
                    ->setCreatedAt(new \DateTime())
                    ->setCarrierName($form->get('carriers')->getData()->getName())
                    ->setCarrierPrice($form->get('carriers')->getData()->getPrice())
                    ->setDelivery($deliveryContent)
                    ->setIsPaid(0);

            $this->orderRepository->add($order, true);

            foreach ($cartComplete as $product) {
                $orderDetails = new OrderDetails();
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($product['product']->getName());
                $orderDetails->setQuantity($product['quantity']);
                $orderDetails->setPrice($product['product']->getPrice());
                $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);
                $this->orderDetailsRepository->add($orderDetails);
            }

            $entityManager->flush();

            return $this->render('order/add.html.twig', [
                'cart' => $cartComplete,
                'carrier' => $form->get('carriers')->getData(),
                'delivery' => $deliveryContent
            ]);
        }

        return $this->redirectToRoute('app_cart');
    }
}
