<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AccountPasswordController extends AbstractController
{
    #[Route('/compte/mot-de-passe', name: 'app_account_password')]
    public function index(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $notification = null;
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $form->get('old_password')->getData();
            if ($passwordHasher->isPasswordValid($user, $oldPassword)) {
                $plainedNewPassword = $form->get('new_password')->getData();
                $hashedNewPassword = $passwordHasher->hashPassword($user, $plainedNewPassword);
                    $notification = "Votre mot de passe a été modifié";
                }
            } else {
            $notification = "Une erreur s'est produite";
        }

        return $this->render('account/password.html.twig', [
            'form' => $form->createView(),
            'notification' => $notification
        ]);
    }
}
