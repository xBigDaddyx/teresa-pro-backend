<?php

namespace App\Application\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    protected const DEFAULT_SUCCESS_CODE = 200;
    protected const DEFAULT_ERROR_CODE = 400;

    protected function successResponse(
        $data,
        string $message = 'Operation completed successfully',
        array $meta = [],
        int $statusCode = self::DEFAULT_SUCCESS_CODE,
        array $headers = []
    ) {
        return ApiResponse::success($data, $message, $meta, $statusCode)->withHeaders($headers);
    }

    protected function errorResponse(
        string $message = 'Something went wrong',
               $errors = null,
        int $statusCode = self::DEFAULT_ERROR_CODE,
        array $headers = []
    ) {
        return ApiResponse::error($message, $errors, $statusCode)->withHeaders($headers);
    }
}
