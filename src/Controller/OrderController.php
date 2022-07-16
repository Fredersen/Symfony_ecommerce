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

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
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
            $date = new \DateTime();

            $delivery = $form->get('addresses')->getData();
            $deliveryContent = $delivery->getFirstName() . " " . $delivery->getLastName();
            if($delivery->getCompany()) {
                $deliveryContent .= "<br/>" . $delivery->getCompany();
            }

            $deliveryContent .= "<br/>" . $delivery->getAdress();
            $deliveryContent .= "<br/>" . $delivery->getPostal() . " " . $delivery->getCity();
            $deliveryContent .= "<br/>" . $delivery->getCountry();

            $order->setUser($this->getUser())
                ->setReference($date->format('dmY').'-'.uniqid())
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
                'delivery' => $deliveryContent,
                'reference' => $order->getReference()
            ]);
        }

        return $this->redirectToRoute('app_cart');
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    #[Route('/commande/checkout/{reference}', name: 'app_order_checkout', methods: ['GET'])]
    public function checkout($reference, OrderRepository $orderRepository, Session $session, ProductRepository $productRepository, Request $request, ManagerRegistry $doctrine)
    {
        $order = $orderRepository->findOneBy(['reference' => $reference]);

        $product_for_stripe = [];
        $YOUR_DOMAIN = 'http://127.0.0.1:8000';

        foreach ($order->getOrderDetails()->getValues() as $product) {
            $productObject = $productRepository->findOneBy(['name' => $product->getProduct()]);
            $product_for_stripe[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $product->getPrice(),
                    'product_data' => [
                        'name' => $product->getProduct(),
                        'description' => $productObject->getDescription(),
                        'images' => [$YOUR_DOMAIN . '/uploads' . $productObject->getIllustration()],
                    ],
                ],
                'quantity' => $product->getQuantity()
            ];
        }

        $product_for_stripe[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $order->getCarrierPrice(),
                'product_data' => [
                    'name' => $order->getCarrierName(),
                ],
            ],
            'quantity' => 1
        ];

        \Stripe\Stripe::setApiKey($this->getParameter('STRIPE_KEY'));

        $checkout_session = \Stripe\Checkout\Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => [
                $product_for_stripe
            ],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/commande/merci/{CHECKOUT_SESSION_ID}',
            'cancel_url' => $YOUR_DOMAIN . '/commande/erreur/{CHECKOUT_SESSION_ID}'
        ]);

        $order->setStripeSessionId($checkout_session->id);

        $orderRepository->add($order, true);

        header("HTTP/1.1 303 See Other");
        return $this->redirect($checkout_session->url);
    }

    #[Route('/commande/merci/{sessionId}', name: 'app_order_success')]
    public function success($sessionId, OrderRepository $orderRepository, Session $session)
    {
        $order = $orderRepository->findOneBy(['stripeSessionId' => $sessionId]);

        if (!$order || $order->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if(!$order->getIsPaid()) {
            $order->setIsPaid(1);
            $orderRepository->add($order, 1);
            $session->remove('cart');
        }

        return $this->render('order/success.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/commande/erreur/{sessionId}', name: 'app_order_failure')]
    public function failure($sessionId, OrderRepository $orderRepository)
    {
        $order = $orderRepository->findOneBy(['stripeSessionId' => $sessionId]);

        if (!$order || $order->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('order/failure.html.twig', [
            'order' => $order
        ]);
    }
}
