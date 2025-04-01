<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for validating user login credentials.
 *
 * Validates email and password fields for user authentication.
 * @package App\Http\Requests
 */
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Always true as no specific authorization is required for login
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for the login request.
     *
     * @return array<string, string|array> Validation rules for request parameters
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    /**
     * Get custom error messages for validation failures.
     *
     * @return array<string, string> Custom messages for validation errors
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    /**
     * PHPDoc block for Scramble to document request body parameters.
     *
     * @bodyParam email string required The user's email address. Must be a valid email format. Example: "john@example.com"
     * @bodyParam password string required The user's password. Example: "Password123"
     *
     * @response 422 array{status: string, error: string, data: null, message: string, errors: array<string, string[]>}
     *     Validation failed with specific error messages (e.g., "Email wajib diisi.", "Format email tidak valid.")
     */
    public function _scrambleDocs() {}
}
