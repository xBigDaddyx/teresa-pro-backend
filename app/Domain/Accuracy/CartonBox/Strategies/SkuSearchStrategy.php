<?php

namespace App\Domain\Accuracy\CartonBox\Strategies;

use App\Infrastructure\Repositories\CartonBoxRepository;
use App\Domain\Accuracy\CartonBox\Entities\CartonBox;

class SkuSearchStrategy implements SearchStrategy
{
    private $repository;

    public function __construct(CartonBoxRepository $repository)
    {
        $this->repository = $repository;
    }

    public function search(string $barcode, ?string $po, ?string $sku): array
    {
        return $this->repository->findByFilters($barcode, $po, $sku);
    }
}
