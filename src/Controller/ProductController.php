<?php

namespace App\Controller;

use App\Classe\Search;
use App\Entity\Product;
use App\Form\SearchType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{

    private ProductRepository $productRepository;

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }


    #[Route('/nos-produits', name: 'app_products')]
    public function index(Request $request): Response
    {

        $products = $this->productRepository->findAll();

        $search = new Search();
        $form = $this->createForm(SearchType::class, $search);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $products = $this->productRepository->findWithSearch($search);
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'form' => $form->createView()
        ]);
    }

    #[Route('/produit/{slug}', name: 'app_product')]
    public function show(Product $product, ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy(['onHomepage'  => 1]);
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'products' => $products
        ]);
    }
}
