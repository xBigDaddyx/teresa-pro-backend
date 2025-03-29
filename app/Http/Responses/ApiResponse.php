<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($data = null, string $message = 'Operation completed successfully', array $meta = [], int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => empty($meta) ? (object) [] : $meta,
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
