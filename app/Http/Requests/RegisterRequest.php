<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Request class for validating user registration data.
 *
 * Validates user registration fields including name, email, and password with confirmation.
 * @package App\Http\Requests
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Always true as no specific authorization is required for registration
     */
    public function authorize(): bool
    {
        return true; // Ubah sesuai kebutuhan otorisasi
    }

    /**
     * Get the validation rules for the registration request.
     *
     * @return array<string, string|array> Validation rules for request parameters
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()->mixedCase()],
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
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama tidak boleh lebih dari 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.letters' => 'Password harus mengandung huruf.',
            'password.numbers' => 'Password harus mengandung angka.',
            'password.mixed' => 'Password harus mengandung huruf besar dan kecil.',
        ];
    }

    /**
     * PHPDoc block for Scramble to document request body parameters.
     *
     * @bodyParam name string required The user's full name. Maximum 255 characters. Example: "John Doe"
     * @bodyParam email string required The user's email address. Must be unique and a valid email format. Example: "john@example.com"
     * @bodyParam password string required The user's password. Must be at least 8 characters with letters, numbers, and mixed case. Example: "Password123"
     * @bodyParam password_confirmation string required Password confirmation. Must match the password field. Example: "Password123"
     *
     * @response 422 array{status: string, error: string, data: null, message: string, errors: array<string, string[]>}
     *     Validation failed with specific error messages (e.g., "Nama wajib diisi.", "Email sudah terdaftar.")
     */
    public function _scrambleDocs() {}
}
