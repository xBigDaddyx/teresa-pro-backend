<?php

namespace App\Application\Services;

use App\Domain\Exceptions\ApiException;
use App\Events\CartonProcessed;
use App\Http\Responses\ApiResponse;
use App\Infrastructure\Repositories\CartonBoxRepository;
use App\Domain\Accuracy\CartonBox\Data\CartonBoxData;
use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\CartonBox\Strategies\BarcodeSearchStrategy;
use App\Domain\Accuracy\CartonBox\Strategies\PoSearchStrategy;
use App\Domain\Accuracy\CartonBox\Strategies\SkuSearchStrategy;
use Illuminate\Support\Facades\URL;

class CartonBoxService
{
    private $repository;
    private $strategies;

    public function __construct(CartonBoxRepository $repository)
    {
        $this->repository = $repository;
        $this->strategies = [
            'barcode' => new BarcodeSearchStrategy($repository),
            'po' => new PoSearchStrategy($repository),
            'sku' => new SkuSearchStrategy($repository),
        ];
    }

    public function search($barcode = null, $po = null, $sku = null, $processedBy = null, $version = 'v1'): \Illuminate\Http\JsonResponse
    {
        $strategy = $this->determineStrategy($barcode, $po, $sku);
        $cartons = $strategy->search($barcode ?? '', $po, $sku);

        if (empty($cartons)) {
            throw new ApiException('Carton not found', 404);
        }

        if (count($cartons) === 1 && $processedBy) {
            $carton = $cartons[0];
            $carton->process($processedBy);
            $this->repository->save($carton);
            $nextStep = URL::to("/api/{$version}/carton-boxes/{$carton->getId()}/validate-item");
            $cartonData = $this->toData($carton, $version, $nextStep); // Tanpa toArray()

            event(new CartonProcessed($carton, $nextStep));
            return ApiResponse::success([$cartonData], 'Carton retrieved and processed successfully');
        }

        $data = array_map(fn($carton) => $this->toData($carton, $version), $cartons); // Tanpa toArray()
        return ApiResponse::success($data, 'Cartons retrieved successfully');
    }

    public function process($id, $processedBy, $version = 'v1'): \Illuminate\Http\JsonResponse
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            throw new ApiException('Carton not found', 404);
        }

        $cartonBox = $this->repository->find($id);
        if (!$cartonBox) {
            throw new ApiException('Carton not found', 404);
        }

        try {
            $cartonBox->process($processedBy);
            $this->repository->save($cartonBox);
            $nextStep = URL::to("/api/{$version}/carton-boxes/{$id}/validate-item");
            $cartonData = $this->toData($cartonBox, $version, $nextStep);

            event(new CartonProcessed($cartonBox, $nextStep));
            return ApiResponse::success($cartonData, 'Carton processed successfully');
        } catch (\Exception $e) {
            throw new ApiException('Processing failed: ' . $e->getMessage(), 500);
        }
    }

    public function getPOsByBarcode($barcode, $version = 'v1'): \Illuminate\Http\JsonResponse
    {
        $cartons = $this->repository->findByFilters($barcode, null, null);
        $pos = collect($cartons)
            ->filter(fn($carton) => $carton->getValidationStatus() !== 'VALIDATED')
            ->map(fn($carton) => $carton->getPackingList()?->getPurchaseOrderNumber())
            ->filter()
            ->unique()
            ->map(fn($po) => ['id' => $po, 'name' => $po])
            ->values()
            ->all();

        return ApiResponse::success($pos, 'POs retrieved successfully');
    }

    public function getSKUsByBarcodeAndPO($barcode, $po, $version = 'v1'): \Illuminate\Http\JsonResponse
    {
        $cartons = $this->repository->findByFilters($barcode, $po, null);
        $skus = collect($cartons)
            ->filter(fn($carton) => $carton->getValidationStatus() !== 'VALIDATED')
            ->map(fn($carton) => $carton->getInternalSku())
            ->filter()
            ->unique()
            ->map(fn($sku) => ['id' => $sku, 'name' => $sku])
            ->values()
            ->all();

        return ApiResponse::success($skus, 'SKUs retrieved successfully');
    }

    private function determineStrategy($barcode, $po, $sku): mixed
    {
        if ($po && $sku) {
            return $this->strategies['sku'];
        } elseif ($po) {
            return $this->strategies['po'];
        } elseif ($sku) {
            return $this->strategies['sku'];
        } else {
            return $this->strategies['barcode'];
        }
    }

    private function toData(CartonBox $cartonBox, $version, $nextStep = null): array
    {
        return [
            'id' => $cartonBox->getId(),
            'barcode' => $cartonBox->getBarcode(),
            'internal_sku' => $cartonBox->getInternalSku(),
            'validation_status' => $cartonBox->getValidationStatus(),
            'status' => $cartonBox->getStatus()->getValue(),
            'processed_at' => $cartonBox->getProcessedAt(),
            'processed_by' => $cartonBox->getProcessedBy(),
            'items_quantity' => $cartonBox->getItemsQuantity(),
            'buyer' => $cartonBox->getPackingList()?->getBuyer(),
            'next_step' => $nextStep,
        ];
    }
}
