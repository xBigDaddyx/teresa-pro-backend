<?php

namespace App\Application\Http\Controllers\V1;

use App\Application\Http\Controllers\BaseController;
use App\Application\Services\CartonBoxService;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class CartonBoxController extends BaseController
{
    private $service;

    public function __construct(CartonBoxService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $barcode = $request->query('barcode');
        $po = $request->query('po');
        $sku = $request->query('sku');
        $processedBy = auth()->id();
        $version = $request->attributes->get('api_version', 'v1');

        try {
            return $this->service->search($barcode, $po, $sku, $processedBy, $version);
        } catch (\Exception $e) {
            $statusCode = $this->sanitizeStatusCode($e->getCode());
            return ApiResponse::error($e->getMessage(), null, $statusCode);
        }
    }

    public function process(Request $request, $id)
    {
        $processedBy = auth()->id();
        $version = $request->attributes->get('api_version', 'v1');

        try {
            return $this->service->process($id, $processedBy, $version);
        } catch (\Exception $e) {
            $statusCode = $this->sanitizeStatusCode($e->getCode());
            return ApiResponse::error($e->getMessage(), null, $statusCode);
        }
    }

    public function getPOs(Request $request)
    {
        $barcode = $request->query('barcode');
        $version = $request->attributes->get('api_version', 'v1');

        if (!$barcode) {
            return ApiResponse::error('Barcode diperlukan', null, 400);
        }

        try {
            return $this->service->getPOsByBarcode($barcode, $version);
        } catch (\Exception $e) {
            $statusCode = $this->sanitizeStatusCode($e->getCode());
            return ApiResponse::error($e->getMessage(), null, $statusCode);
        }
    }

    public function getSKUs(Request $request)
    {
        $barcode = $request->query('barcode');
        $po = $request->query('po');
        $version = $request->attributes->get('api_version', 'v1');

        if (!$barcode || !$po) {
            return ApiResponse::error('Barcode dan PO diperlukan', null, 400);
        }

        try {
            return $this->service->getSKUsByBarcodeAndPO($barcode, $po, $version);
        } catch (\Exception $e) {
            $statusCode = $this->sanitizeStatusCode($e->getCode());
            return ApiResponse::error($e->getMessage(), null, $statusCode);
        }
    }

    private function sanitizeStatusCode($code): int
    {
        $code = (int) $code;
        return ($code >= 100 && $code <= 599) ? $code : 500;
    }
}
