<?php

namespace App\Application\Services;

use App\Infrastructure\Repositories\PackingListServiceRepository;

class PackingListService
{
    private $repository;

    public function __construct(PackingListServiceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute($data): void
    {
        $entity = $this->repository->find($data['id']);
        if ($entity) {
            // Contoh operasi
        }
    }
}
