<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\ResetPasswordType;
use App\Repository\ResetPasswordRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ResetPasswordController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'app_reset_password')]
    public function index(Request $request, UserRepository $userRepository, ResetPasswordRepository $resetPasswordRepository): Response
    {

        if($this->getUser())
        {
            return $this->redirectToRoute('app_home');
        }

        if ($request->get('email')) {
            $user = $userRepository->findOneBy(['email' => $request->get('email')]);

            if($user) {
                $resetPassword = new ResetPassword();
                $resetPassword->setUser($user)
                                ->setToken(uniqid())
                                ->setCreatedAt(new \DateTime());
                $resetPasswordRepository->add($resetPassword, 1);

                $url = $this->generateUrl('app_update_password', ["token" => $resetPassword->getToken()]);
                $content = "Bonjour " . $user->getFirstname() . ", vous avez demander de réinitialiser votre mot de passe <br/>";
                $content .= "Merci de bien vouloir cliquer sur le lien suivant<a href=" . $url . "> pour mettre à jour votre mot de passe </a>";

                $mail = new Mail();
                $mail->send($user->getEmail(), $user->getFirstname(), $user->getLastname(), $content);
                $this->addFlash('notice', 'Un mail vient de vous être envoye. Merci de vérifier votre boite mail.');

            } else {
                $this->addFlash('notice', 'Cette adresse mail est inconnu');

            }
        }

        return $this->render('reset_password/index.html.twig');
    }

    #[Route('/mot-de-passe-oublie/{token}', name: 'app_update_password')]
    public function update($token, ResetPasswordRepository $resetPasswordRepository, Request $request, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): Response
    {
        $resetPassword = $resetPasswordRepository->findOneBy(['token' => $token]);
        if(!$resetPassword) {
            return $this->redirectToRoute('app_reset_password');
        }

        $now = new \DateTime();
        if ($now > $resetPassword->getCreatedAt()->modify('+ 1 hour')) {
           $this->addFlash('notice', 'Votre demande de mot de passe à expiré. Merci de la renouvelle');
           return $this->redirectToRoute('app_reset_password');

        }

        $form = $this->createForm(ResetPasswordType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainedNewPassword = $form->get('new_password')->getData();
            $user = $resetPassword->getUser();

            $hashedNewPassword = $passwordHasher->hashPassword($user, $plainedNewPassword);
            $user->setPassword($hashedNewPassword);
            $userRepository->add($user, 1);

            $this->addFlash('notice', 'Votre mot de passe a bien été modifié');
            return $this->redirectToRoute('app_login');
        }


        return $this->render('reset_password/update.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
