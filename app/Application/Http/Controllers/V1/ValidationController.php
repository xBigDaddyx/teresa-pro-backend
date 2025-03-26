<?php

namespace App\Application\Http\Controllers\V1;

use App\Application\Http\Controllers\BaseController;
use App\Application\Services\ValidationService;
use Illuminate\Http\Request;

class ValidationController extends BaseController
{
    private $service;

    public function __construct(ValidationService $service)
    {
        $this->service = $service;
    }

    public function validateItem(Request $request, $id)
    {
        $barcode = $request->input('barcode');
        $validatedBy = auth()->id(); // Ambil dari auth

        if (!$barcode || !$validatedBy) {
            return $this->errorResponse('barcode dan validated_by diperlukan', 400);
        }

        try {
            $result = $this->service->validateCartonItem($id, $barcode, $validatedBy);
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
}
