<?php

namespace App\Application\Services;

use App\Domain\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

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

        $token = $user->createToken('api-token')->plainTextToken;

        Log::info('User registered successfully', ['email' => $email]);
        return $token;
    }

    public function login(string $email, string $password): ?string
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            Log::warning('Login failed', ['email' => $email]);
            return null;
        }

        if (!$user->hasVerifiedEmail()) {
            Log::warning('Login failed - email not verified', ['email' => $email]);
            return null;
        }

        $token = $user->createToken('api-token')->plainTextToken;
        Log::info('User logged in', ['email' => $email]);
        return $token;
    }

    public function logout($user): void
    {
//        $user->currentAccessToken()->delete();
        $user->tokens()->delete(); // Delete all tokens, including refresh tokens
        Log::info('User logged out', ['user_id' => $user->id]);
    }

    public function refreshToken(string $currentRefreshToken): array
    {
        $refreshToken = PersonalAccessToken::findToken($currentRefreshToken);

        if (!$refreshToken || !$refreshToken->can('refresh') || ($refreshToken->expires_at && $refreshToken->expires_at->isPast())) {
            throw new \Exception('Invalid or expired refresh token');
        }

        $user = $refreshToken->tokenable;
        $refreshToken->delete();

        $accessTokenExpiresAt = Carbon::now()->addDays(1);
        $refreshTokenExpiresAt = Carbon::now()->addDays(7);

        $newAccessToken = $user->createToken('access_token', ['*'], $accessTokenExpiresAt)->plainTextToken;
        $newRefreshToken = $user->createToken('refresh_token', ['refresh'], $refreshTokenExpiresAt)->plainTextToken;

        Log::info('Token refreshed', ['user_id' => $user->id]);
        return [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
        ];
    }
}
