<?php

namespace App\Controller\Admin\Administration;

use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

class TwoFactorSetupController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/2fa/setup', name: '2fa_setup')]
    public function setup(#[CurrentUser] $user, GoogleAuthenticatorInterface $googleAuthenticator): Response
    {
        if (!$user instanceof TwoFactorInterface) {
            throw $this->createAccessDeniedException('User does not support 2FA.');
        }

        if (!$user->getGoogleAuthenticatorSecret()) {
            $secret = $googleAuthenticator->generateSecret();
            $user->setGoogleAuthenticatorSecret($secret);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $qrContent = $googleAuthenticator->getQRContent($user);
        $qrCode = new QrCode($qrContent);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $this->render('security/2fa_setup.html.twig', [
            'qrCode' => base64_encode($result->getString()),
            'secret' => $user->getGoogleAuthenticatorSecret(),
        ]);
    }
}