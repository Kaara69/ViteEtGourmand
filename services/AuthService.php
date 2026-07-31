<?php

require_once __DIR__ . '/../repositories/UserRepository.php';

class AuthService
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom']     = $user['nom'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        return $user;
    }

    public function getRedirectUrl(string $role): string
    {
        return match ($role) {
            'client' => 'user/dashboard.php',
            'employee' => 'employee/dashboard.php',
            'admin' => 'admin/dashboard.php',
            default => 'login.php'
        };
    }
}