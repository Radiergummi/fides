<?php

namespace App\Security;

use App\Form\LoginFormType;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class FidesAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const CSRF_TOKEN_ID = 'authenticate';

    public const LOGIN_ROUTE = 'app.auth.login';

    public const MIN_PASSWORD_LENGTH = 8;

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * @param Request $request
     *
     * @return Passport
     * @throws InvalidArgumentException
     * @throws BadRequestException
     * @throws SessionNotFoundException
     */
    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get(
            LoginFormType::FIELD_USERNAME,
            ''
        );

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get(
                LoginFormType::FIELD_PASSWORD,
                ''
            )),
            [
                new CsrfTokenBadge(
                    self::CSRF_TOKEN_ID,
                    $request->request->get(
                        LoginFormType::FIELD_CSRF_TOKEN
                    )
                ),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return string
     * @throws InvalidParameterException
     * @throws MissingMandatoryParametersException
     * @throws RouteNotFoundException
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $firewallName
     *
     * @return Response|null
     * @throws InvalidArgumentException
     * @throws SessionNotFoundException
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): Response|null {
        if ($targetPath = $this->getTargetPath(
            $request->getSession(),
            $firewallName
        )) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate(
            'index'
        ));
    }
}
