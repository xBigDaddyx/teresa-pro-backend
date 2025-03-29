<?php

namespace App\Application\Http\Controllers\V1;

use App\Application\Http\Controllers\BaseController;
use App\Application\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()->mixedCase()],
            ]);

            $token = $this->authService->register(
                $request->input('name'),
                $request->input('email'),
                $request->input('password')
            );

            if (!$token) {
                return $this->errorResponse('Failed to generate token', 500);
            }

            return $this->successResponse(
                ['token' => $token], // $data
                'User registered successfully. Please verify your email.', // $message
                [], // $meta
                200 // $statusCode
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $token = $this->authService->login(
                $request->input('email'),
                $request->input('password')
            );

            if ($token) {
                return $this->successResponse(
                    ['token' => $token], // $data
                    'Login successful', // $message
                    [], // $meta
                    200 // $statusCode
                );
            }

            return $this->errorResponse('Invalid credentials or email not verified', 401);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->errorResponse('Login failed: ' . $e->getMessage(), 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            $this->authService->logout($user);
            return $this->successResponse(
                null, // $data
                'Logged out successfully', // $message
                [], // $meta
                200 // $statusCode
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed: ' . $e->getMessage(), 500);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            $token = $this->authService->refreshToken($user);

            if (!$token) {
                return $this->errorResponse('Failed to refresh token', 500);
            }

            return $this->successResponse(
                ['token' => $token], // $data
                'Token refreshed successfully', // $message
                [], // $meta
                200 // $statusCode
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Token refresh failed: ' . $e->getMessage(), 500);
        }
    }
}
