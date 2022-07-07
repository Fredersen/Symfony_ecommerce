<?php

namespace App\Controller;

use App\Entity\Adress;
use App\Form\AdressType;
use App\Repository\AdressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccountAdressController extends AbstractController
{
    #[Route('/compte/adresses', name: 'app_account_adress')]
    public function index(): Response
    {
        return $this->render('account/adress.html.twig', [
        ]);
    }

    #[Route('/compte/ajouter-une-adresse', name: 'app_account_adress_add')]
    public function add(Request $request, AdressRepository $adressRepository): Response
    {
        $address = new Adress();
        $form = $this->createForm(AdressType::class, $address);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $address->setUser($this->getUser());
            $adressRepository->add($address, true);

           return $this->redirectToRoute('app_account_adress');
        }

        return $this->render('account/adress_form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/compte/modifier-une-adresse/{id}', name: 'app_account_adress_edit')]
    public function edit(Request $request, AdressRepository $adressRepository, $id): Response
    {
        $address = $adressRepository->find($id);

        if (!$address || $address->getUser() != $this->getUser()) {
            return $this->redirectToRoute('app_account_adress');
        }

        $form = $this->createForm(AdressType::class, $address);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $address->setUser($this->getUser());
            $adressRepository->add($address, true);

            return $this->redirectToRoute('app_account_adress');
        }

        return $this->render('account/adress_form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/compte/supprimer-une-adresse/{id}', name: 'app_account_adress_delete')]
    public function delete(Request $request, AdressRepository $adressRepository, $id): Response
    {
        $address = $adressRepository->find($id);

        if ($address && $address->getUser() == $this->getUser()) {
            $adressRepository->remove($address, true);
        }

        return $this->redirectToRoute('app_account_adress');
    }
}
