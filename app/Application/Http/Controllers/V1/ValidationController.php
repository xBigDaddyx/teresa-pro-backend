<?php

namespace App\Application\Http\Controllers\V1;

use App\Application\Http\Controllers\BaseController;
use App\Application\Services\ValidationService;
use Illuminate\Http\Request;

/**
 * Controller untuk menangani validasi carton box dan item di API V1.
 */
class ValidationController extends BaseController
{
    /**
     * Service untuk melakukan validasi carton box dan item.
     *
     * @var ValidationService
     */
    private $service;

    /**
     * Membuat instance baru dari ValidationController.
     *
     * @param ValidationService $service Service untuk validasi carton box
     */
    public function __construct(ValidationService $service)
    {
        $this->service = $service;
    }

    /**
     * Memvalidasi item dalam carton box berdasarkan barcode.
     *
     * Endpoint ini menerima barcode item dan melakukan validasi terhadap
     * carton box dengan ID yang diberikan.
     *
     * @param Request $request Request HTTP yang berisi data validasi
     * @param mixed $id ID dari carton box yang akan divalidasi
     *
     * @return \Illuminate\Http\JsonResponse Response JSON dengan hasil validasi atau pesan error
     */
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
