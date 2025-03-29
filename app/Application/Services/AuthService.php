<?php

namespace App\Application\Services;

use App\Domain\Accuracy\Shared\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class AuthService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(string $name, string $email, string $password): string
    {
        $user = $this->userRepository->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        event(new Registered($user));

        return $user->createToken('api-token')->plainTextToken;
    }

    public function login(string $email, string $password): ?string
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        if (!$user->hasVerifiedEmail()) {
            return null;
        }

        return $user->createToken('api-token')->plainTextToken;
    }

    public function logout($user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function refreshToken($user): string
    {
        $user->tokens()->delete();
        return $user->createToken('api-token')->plainTextToken;
    }
}
