<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($data = null, string $message = 'Operation completed successfully', int $statusCode = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => empty($meta) ? (object) [] : $meta, // Pastikan meta adalah objek kosong jika tidak ada data
            'timestamp' => now()->toIso8601String(),
        ], $statusCode);
    }

    public static function error(string $message = 'Something went wrong', $errors = null, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors ?: (object) [],
            'timestamp' => now()->toIso8601String(),
        ], $statusCode);
    }
}
