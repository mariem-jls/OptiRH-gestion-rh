<?php

namespace App\Controller\Admin\Administration;

use App\Entity\User;
use App\Form\Admin\User\UserType;
use App\Repository\UserRepository;
use App\Form\Admin\User\ProfileType;
use App\Form\Admin\User\EditUserType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/users', name: 'admin_users', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, PaginatorInterface $paginator): Response
    {
        $searchTerm = $request->query->get('q', '');

        $queryBuilder = $userRepository->createQueryBuilder('u');
        if ($searchTerm) {
            $queryBuilder
                ->where('u.nom LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('Administration/Users/indexUsers.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/users/new', name: 'admin_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setIsVerified(true);
            $user->setCreatedAt(new \DateTimeImmutable('now'));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('Administration/Users/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}', name: 'admin_users_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('Administration/Users/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(EditUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('Administration/Users/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}', name: 'admin_users_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $user->setRoles(['ROLE_USER']);
            $userRepository->remove($user);
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/profile', name: 'admin_users_profile', methods: ['GET'])]
    public function profile(User $user): Response
    {
        $form = $this->createForm(ProfileType::class, $user);

        return $this->render('Administration/Users/user-profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}/profile/update', name: 'admin_users_profile_update', methods: ['POST'])]
    public function updateProfile(Request $request, User $user): Response
    {
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile) {
                $newFilename = 'user_' . $user->getId() . '.' . $avatarFile->guessExtension();
                try {
                    $avatarFile->move(
                        $this->getParameter('avatars_directory'),
                        $newFilename
                    );
                    $user->setAvatar('uploads/avatars/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'avatar.');
                }
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('admin_users_profile', ['id' => $user->getId()]);
        }

        return $this->render('Administration/Users/user-profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}/profile/2fa/toggle', name: 'admin_users_profile_2fa_toggle', methods: ['POST'])]
    public function toggle2FA(User $user): Response
    {
        if ($user->getGoogleAuthenticatorSecret()) {
            $user->setGoogleAuthenticatorSecret(null);
            $this->addFlash('success', 'Authentification à deux facteurs désactivée.');
        } else {
            $this->addFlash('info', 'Veuillez configurer l\'authentification à deux facteurs.');
            return $this->redirectToRoute('2fa_setup');
        }

        $this->entityManager->flush();
        return $this->redirectToRoute('admin_users_profile', ['id' => $user->getId()]);
    }
}