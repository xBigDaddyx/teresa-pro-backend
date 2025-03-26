<?php

namespace App\Application\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    protected function successResponse($data, string $message = 'Operation completed successfully', array $meta = [], int $statusCode = 200)
    {
        return ApiResponse::success($data, $message, $meta, $statusCode);
    }

    protected function errorResponse(string $message = 'Something went wrong', int $statusCode = 400, $errors = null)
    {
        return ApiResponse::error($message, $errors, $statusCode);
    }
}
