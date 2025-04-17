<?php
namespace App\EventListener;

use App\Security\TurnstileValidator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class TurnstileLoginListener
{
    private TurnstileValidator $validator;
    private RouterInterface $router;
    private RequestStack $requestStack;

    public function __construct(
        TurnstileValidator $validator,
        RouterInterface $router,
        RequestStack $requestStack
    ) {
        $this->validator = $validator;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only check login POST requests
        if ($request->getPathInfo() !== '/login' || $request->getMethod() !== 'POST') {
            return;
        }

        $token = $request->request->get('cf-turnstile-response');
        $ip = $request->getClientIp();

        if (!$token || !$this->validator->isValid($token, $ip)) {
            $request->getSession()->getFlashBag()->add('error', 'CAPTCHA non valide.');
            $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
        }
    }
}
