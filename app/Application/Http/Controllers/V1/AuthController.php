<?php

namespace App\Application\Http\Controllers\V1;

use App\Application\Http\Controllers\BaseController;
use App\Application\Services\AuthService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Infrastructure\Tenancy\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends BaseController
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $tenant = TenantManager::getCurrent();
            if (!$tenant) {
                return $this->errorResponse('Tenant not found', null, 403);
            }

            $token = $this->authService->register(
                $request->input('name'),
                $request->input('email'),
                $request->input('password')
            );

            return $this->successResponse(
                ['token' => $token],
                'User registered successfully. Please verify your email.'
            );
        } catch (\Exception $e) {
            Log::error('Registration failed', ['error' => $e->getMessage(), 'email' => $request->input('email')]);
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $tenant = TenantManager::getCurrent();
            if (!$tenant) {
                return $this->errorResponse('Tenant not found', null, 403);
            }

            $token = $this->authService->login(
                $request->input('email'),
                $request->input('password')
            );

            if ($token) {
                return $this->successResponse(
                    ['token' => $token],
                    'Login successful'
                );
            }

            return $this->errorResponse('Invalid credentials or email not verified', null, 401);
        } catch (\Exception $e) {
            Log::error('Login error', ['error' => $e->getMessage(), 'email' => $request->input('email')]);
            return $this->errorResponse('Login failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('User not authenticated', null, 401);
            }

            $this->authService->logout($user);
            return $this->successResponse(null, 'Logged out successfully');
        } catch (\Exception $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Logout failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $currentRefreshToken = $request->bearerToken();
            if (!$currentRefreshToken) {
                return $this->errorResponse('Unauthenticated - Refresh token is required', null, 401);
            }

            $tokens = $this->authService->refreshToken($currentRefreshToken);

            return $this->successResponse(
                ['token' => $tokens['access_token'], 'refresh_token' => $tokens['refresh_token']],
                'Token refreshed successfully'
            );
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Invalid or expired refresh token') {
                return $this->errorResponse('Invalid refresh token', null, 401);
            }
            Log::error('Refresh failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Token refresh failed: ' . $e->getMessage(), null, 500);
        }
    }
}
