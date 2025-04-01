<?php

namespace App\Application\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Base controller providing standardized API response methods.
 * @package App\Application\Http\Controllers
 */
class BaseController extends Controller
{
    /** @var int Default HTTP status code for successful responses */
    protected const DEFAULT_SUCCESS_CODE = 200;

    /** @var int Default HTTP status code for error responses */
    protected const DEFAULT_ERROR_CODE = 400;

    /**
     * Create a successful API response.
     *
     * @param mixed $data Response data to be included
     * @param string $message Success message
     * @param array<string, mixed> $meta Additional metadata
     * @param int $statusCode HTTP status code
     * @param array<string, string> $headers Additional HTTP headers
     * @return JsonResponse
     *
     * @response 200 array{data: mixed, message: string, meta: array<string, mixed>} Successful response
     */
    protected function successResponse(
        $data,
        string $message = 'Operation completed successfully',
        array $meta = [],
        int $statusCode = self::DEFAULT_SUCCESS_CODE,
        array $headers = []
    ) {
        // Standardized success response
        return ApiResponse::success($data, $message, $meta, $statusCode)->withHeaders($headers);
    }

    /**
     * Create an error API response.
     *
     * @param string $message Error message
     * @param mixed $errors Error details (can be null, string, or array)
     * @param int $statusCode HTTP status code
     * @param array<string, string> $headers Additional HTTP headers
     * @return JsonResponse
     *
     * @response 400 array{error: string, data: null, message: string} Error response
     */
    protected function errorResponse(
        string $message = 'Something went wrong',
               $errors = null,
        int $statusCode = self::DEFAULT_ERROR_CODE,
        array $headers = []
    ) {
        // Standardized error response
        return ApiResponse::error($message, $errors, $statusCode)->withHeaders($headers);
    }
}
