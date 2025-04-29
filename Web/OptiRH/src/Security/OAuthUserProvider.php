<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class OAuthUserProvider implements OAuthAwareUserProviderInterface, UserProviderInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): ?UserInterface
    {
        $email = $response->getEmail();
        $googleId = $response->getUsername();
        $fullName = $response->getRealName();
        $avatarUrl = $response->getProfilePicture(); 

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setGoogleId($googleId);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword('');

            if ($fullName) {
                $user->setNom($fullName);
            } else {
                $user->setNom('Unknown');
            }

            $user->setAvatar($avatarUrl);
            $user->setCreatedAt(new \DateTime());
            $user->setIsVerified(true);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } elseif (!$user->getGoogleId()) {
            $user->setGoogleId($googleId);
            $user->setAvatar($avatarUrl);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $identifier]);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User with identifier "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $refreshedUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);

        if (!$refreshedUser) {
            throw new UserNotFoundException(sprintf('User with email "%s" not found.', $user->getEmail()));
        }

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}