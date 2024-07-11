<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
//use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Security\CustomAuthenticator;
use Namshi\JOSE\Signer\OpenSSL\None;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\CartItem;


class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private TokenStorageInterface $tokenStorage;


    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, TokenStorageInterface $tokenStorage)
    {

        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/api/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $this->tokenStorage->setToken(null);
        return new JsonResponse(['msg' => 'Logged out successfully'], Response::HTTP_OK);
    }

    #[Route('/api/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {

        $requestData = json_decode($request->getContent(), true);

        if (!isset($requestData['email'], $requestData['password'])) {
            return new JsonResponse(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }

        $email = $requestData['email'];
        $password = $requestData['password'];

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !password_verify($password, $user->getPassword())) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->generateToken($user->getEmail());

        return new JsonResponse(['token' => $token]);
    }

    public function generateToken(string $email): string
    {

        $payload = [
            'email' => $email,
            'exp' => time() + 3600
        ];


        $secretKey = $_ENV['JWT_SECRET_KEY'];

        if (!$secretKey) {
            throw new \Exception('JWT_SECRET environment variable not found.');
        }


        return JWT::encode($payload, $secretKey, 'HS256');
    }

    #[Route('/api/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        if (!isset($requestData['email'], $requestData['password'], $requestData['firstname'], $requestData['lastname'])) {
            return new JsonResponse(['error' => 'Email, password, firstname, lastname are required'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userRepository->findOneBy(['email' => $requestData['email']])) {
            return new JsonResponse(['error' => 'Email already in use'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($requestData['email']);
        $user->setPassword(password_hash($requestData['password'], PASSWORD_BCRYPT));
        $user->setFirstname($requestData['firstname']);
        $user->setLastname($requestData['lastname']);
        $role = $requestData['role'] ?? 'ROLE_USER';
        $user->setRole($role);
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();


        return new JsonResponse(['msg' => 'User registered successfully'], Response::HTTP_CREATED);
    }
    #[Route('/api/get-user', name: 'get_user', methods: ['GET'])]
    public function getAuthenticatedUser(): JsonResponse
    {
        $user =
            $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'email' => $user->getUserIdentifier(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'roles' => $user->getRoles(),
        ]);
    }
    #[Route('/api/get-user', name: 'update_user', methods: ['POST'])]
    public function UpdateAuthenticatedUser(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);



        $user = $this->userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        if (isset($requestData['email'])) {
            $user->setEmail($requestData['email']);
        }
        if (isset($requestData['firstname'])) {
            $user->setFirstname($requestData['firstname']);
        }
        if (isset($requestData['lastname'])) {
            $user->setLastname($requestData['lastname']);
        }
        if (isset($requestData['password'])) {
            $user->setPassword(password_hash($requestData['password'], PASSWORD_BCRYPT));
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return new JsonResponse(['msg' => 'User updated successfully'], Response::HTTP_OK);
    }
    #[Route('/api/get-user', name: 'delete_user', methods: ['DELETE'])]
    public function deleteAuthenticatedUser(): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(['msg' => 'User deleted successfully'], Response::HTTP_OK);
    }
    #[Route('api/get-all-users', name: 'get_all_users', methods: ['GET'])]
    public function getAllUsers(AuthorizationCheckerInterface $authChecker, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }
        $users = $this->userRepository->findAll();
        $data = [];
        foreach ($users as $user) {
            $cart = $user->getCart();
            $data[] = [
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'roles' => $user->getRoles(),
                'id' => $user->getId(),
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }
    #[Route('api/update-user-role', name: 'update_user_role', methods: ['POST'])]
    public function updateUserRole(AuthorizationCheckerInterface $authChecker, Request $request): JsonResponse
    {
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }
        $requestData = json_decode($request->getContent(), true);
        if (!isset($requestData['email'], $requestData['role'])) {
            return new JsonResponse(['error' => 'Email and role are required'], Response::HTTP_BAD_REQUEST);
        }
        $user = $this->userRepository->findOneBy(['email' => $requestData['email']]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $user->setRole($requestData['role']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return new JsonResponse(['msg' => 'User role updated successfully'], Response::HTTP_OK);
    }
    private function getUserRole(string $username): ?string
    {
        return $this->userRepository->loadUserByRole($username);
    }
}
