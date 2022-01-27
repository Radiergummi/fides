<?php

namespace App\Controller;

use App\Form\LoginFormType;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @param AuthenticationUtils $authenticationUtils
     * @param Request             $request
     *
     * @return Response
     * @throws LogicException
     */
    #[Route(path: '/login', name: 'app.auth.login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        $form = $this->createForm(LoginFormType::class);

        if ($error = $authenticationUtils->getLastAuthenticationError()) {
            $form->addError(new FormError($error->getMessage()));
        }

        return $this->renderForm('security/login.html.twig', [
            'loginForm' => $form,
        ]);
    }

    /**
     * @throws LogicException
     */
    #[Route(path: '/logout', name: 'app.auth.logout')]
    public function logout(): void
    {
        throw new LogicException(
            'This method can be blank - it will be intercepted by ' .
            'the logout key on your firewall.'
        );
    }
}
