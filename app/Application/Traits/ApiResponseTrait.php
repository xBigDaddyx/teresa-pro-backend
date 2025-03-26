<?php

namespace App\Application\Traits;

trait ApiResponseTrait
{
    protected function successResponse($data, ?string $message = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message ?? 'Operation successful',
            'code' => $code,
        ], $code);
    }

    protected function errorResponse(string $message, int $code, array $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
        ], $code);
    }

    protected function paginatedResponse($paginator, ?string $message = null)
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'message' => $message ?? 'Data retrieved successfully',
            'code' => 200
        ]);
    }
}
