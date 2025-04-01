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

/**
 * Handles authentication-related operations including registration, login, logout, and token refresh.
 * @package App\Application\Http\Controllers\V1
 */
class AuthController extends BaseController
{
    /** @var AuthService $authService Authentication service instance */
    protected $authService;

    /**
     * Get standardized logging context with request-specific information.
     *
     * @param Request $request The incoming HTTP request
     * @param array $additional Additional context data to include
     * @return array<string, mixed> Logging context array
     */
    private function getLogContext($request, array $additional = []): array
    {
        $email = $request->input('email');
        return array_merge([
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tenant_id' => TenantManager::getCurrent()?->id ?? 'unknown',
            'request_id' => uniqid('req_'),
            'correlation_id' => $request->header('X-Correlation-ID', uniqid('corr_')),
            'email' => $email ? (substr($email, 0, 3) . '***@***' . substr($email, -3)) : null,
        ], $additional);
    }

    /**
     * AuthController constructor.
     *
     * @param AuthService $authService Authentication service dependency
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        Log::setDefaultDriver('auth');
    }

    /**
     * Register a new user account and return an access token.
     *
     * Creates a new user with the provided credentials, triggers a Registered event,
     * and generates an initial API token. Email verification is required before login.
     * Requires X-Tenant-ID header for tenant context.
     *
     * @param RegisterRequest $request Validated registration request containing name, email, password, and password_confirmation
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 201 array{status: string, data: array{token: string}, message: string, meta: array<string, mixed>}
     *     User successfully registered with initial access token
     * @response 403 array{status: string, error: string, data: null, message: string}
     *     Tenant not found for the provided X-Tenant-ID
     * @response 422 array{status: string, error: string, data: null, message: string, errors: array<string, string[]>}
     *     Validation failed (e.g., invalid email, short password, duplicate email)
     * @response 500 array{status: string, error: string, data: null, message: string}
     *     Registration failed due to server error or database issue
     */
    public function register(RegisterRequest $request)
    {
        $startTime = microtime(true);
        $context = $this->getLogContext($request, [
            'action' => 'register',
            'raw_email' => $request->input('email')
        ]);

        Log::info('Registration attempt started', $context);

        try {
            $tenant = TenantManager::getCurrent();
            if (!$tenant) {
                Log::warning('Registration failed: No tenant found', $context);
                return $this->errorResponse('Tenant not found', null, 403);
            }

            $token = $this->authService->register(
                $request->input('name'),
                $context['raw_email'],
                $request->input('password')
            );

            $context['duration_ms'] = (microtime(true) - $startTime) * 1000;
            Log::info('Registration successful', $context);

            // User registered successfully with initial API token
            return $this->successResponse(
                ['token' => $token],
                'User registered successfully. Please verify your email.',
                [],
                201
            );
        } catch (\Exception $e) {
            $context['error'] = $e->getMessage();
            $context['trace'] = $e->getTraceAsString();
            $context['duration_ms'] = (microtime(true) - $startTime) * 1000;
            Log::error('Registration failed', $context);
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Authenticate a user and return an access token.
     *
     * Validates credentials against stored user data. Requires email verification and correct tenant context (X-Tenant-ID).
     * Implements rate limiting after 5 failed attempts.
     *
     * @param LoginRequest $request Validated login request containing email and password
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 array{status: string, data: array{token: string}, message: string, meta: array<string, mixed>}
     *     Successful login with new access token
     * @response 401 array{status: string, error: string, data: null, message: string}
     *     Invalid credentials or email not verified
     * @response 403 array{status: string, error: string, data: null, message: string}
     *     Tenant not found for the provided X-Tenant-ID
     * @response 429 array{status: string, error: string, data: null, message: string}
     *     Too many login attempts (rate limit exceeded)
     * @response 500 array{status: string, error: string, data: null, message: string}
     *     Login failed due to server error
     */
    public function login(LoginRequest $request)
    {
        $startTime = microtime(true);
        $context = $this->getLogContext($request, [
            'action' => 'login',
            'raw_email' => $request->input('email')
        ]);

        Log::info('Login attempt started', $context);

        try {
            $tenant = TenantManager::getCurrent();
            if (!$tenant) {
                Log::warning('Login failed: No tenant found', $context);
                return $this->errorResponse('Tenant not found', null, 403);
            }

            $token = $this->authService->login(
                $context['raw_email'],
                $request->input('password')
            );

            if ($token) {
                $context['duration_ms'] = (microtime(true) - $startTime) * 1000;
                Log::info('Login successful', $context);

                // Successful login with new access token
                return $this->successResponse(
                    ['token' => $token],
                    'Login successful'
                );
            }

            $context['duration_ms'] = (microtime(true) - $startTime) * 1000;
            Log::notice('Login failed: Invalid credentials', $context);
            return $this->errorResponse('Invalid credentials or email not verified', null, 401);
        } catch (\Exception $e) {
            $context['error'] = $e->getMessage();
            $context['trace'] = $e->getTraceAsString();
            $context['duration_ms'] = (microtime(true) - $startTime) * 1000;
            Log::error('Login failed', $context);
            return $this->errorResponse('Login failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Log out an authenticated user by revoking all tokens.
     *
     * Deletes all personal access tokens for the authenticated user,
     * effectively ending all active sessions. Requires X-Tenant-ID header.
     *
     * @param Request $request HTTP request with authenticated user
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 array{status: string, data: null, message: string, meta: array<string, mixed>}
     *     Successful logout, all tokens revoked
     * @response 401 array{status: string, error: string, data: null, message: string}
     *     User not authenticated (no valid token provided)
     * @response 500 array{status: string, error: string, data: null, message: string}
     *     Logout failed due to server error
     */
    public function logout(Request $request)
    {
        $startTime = microtime(true);
        $context = $this->getLogContext($request, [
            'action' => 'logout',
            'user_id' => $request->user()?->id ?? 'unknown'
        ]);

        Log::info('Logout attempt started', $context);

        try {
            $user = $request->user();
            if (!$user) {
                Log::warning('Logout failed: User not authenticated', $context);
                return $this->errorResponse('User not authenticated', null, 401);
            }

            $this->authService->logout($user);
            $context['duration_ms'] = (microtime(true) - $startTime) * 1000;
            Log::info('Logout successful', $context);

            // Successful logout, all tokens revoked
            return $this->successResponse(null, 'Logged out successfully');
        } catch (\Exception $e) {
            $context['error'] = $e->getMessage();
            $context['trace'] = $e->getTraceAsString();
            $context['duration_ms'] = (microtime(true) - $startTime) * 1000;
            Log::error('Logout failed', $context);
            return $this->errorResponse('Logout failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Refresh an authentication token using a refresh token.
     *
     * Validates the provided refresh token (via Authorization: Bearer header), revokes it,
     * and issues new access (1-day expiry) and refresh (7-day expiry) tokens.
     * Requires X-Tenant-ID header.
     *
     * @param Request $request HTTP request with Bearer refresh token in Authorization header
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 array{status: string, data: array{token: string, refresh_token: string}, message: string, meta: array<string, mixed>}
     *     Successful token refresh with new access and refresh tokens
     * @response 401 array{status: string, error: string, data: null, message: string}
     *     Invalid, expired, missing, or previously revoked refresh token
     * @response 500 array{status: string, error: string, data: null, message: string}
     *     Token refresh failed due to server error
     * @throws \Exception If refresh token is invalid or expired
     */
    public function refresh(Request $request)
    {
        $startTime = microtime(true);
        $context = $this->getLogContext($request, [
            'action' => 'refresh_token'
        ]);

        Log::info('Token refresh attempt started', $context);

        try {
            $currentRefreshToken = $request->bearerToken();
            if (!$currentRefreshToken) {
                Log::warning('Token refresh failed: No token provided', $context);
                return $this->errorResponse('Unauthenticated - Refresh token is required', null, 401);
            }

            $tokens = $this->authService->refreshToken($currentRefreshToken);

            $context['duration_ms'] = (microtime(true) - $startTime) * 1000;
            Log::info('Token refresh successful', $context);

            // Successful token refresh with new tokens
            return $this->successResponse(
                ['token' => $tokens['access_token'], 'refresh_token' => $tokens['refresh_token']],
                'Token refreshed successfully'
            );
        } catch (\Exception $e) {
            $context['error'] = $e->getMessage();
            $context['trace'] = $e->getTraceAsString();
            $context['duration_ms'] = (microtime(true) - $startTime) * 1000;

            if ($e->getMessage() === 'Invalid or expired refresh token') {
                Log::notice('Token refresh failed: Invalid token', $context);
                return $this->errorResponse('Invalid refresh token', null, 401);
            }

            Log::error('Token refresh failed', $context);
            return $this->errorResponse('Token refresh failed: ' . $e->getMessage(), null, 500);
        }
    }
}
