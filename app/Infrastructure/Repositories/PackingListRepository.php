<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Tenancy\TenantManager;

class PackingListRepository
{
    protected $db;

    public function __construct()
    {
        $this->db = app('db')->connection('tenant'); // Koneksi tenant untuk PostgreSQL
    }

    public function find($id)
    {
        return $this->db->table('PackingListRepository')->where('id', $id)->first();
    }

    public function save($entity): void
    {
        $this->db->table('PackingListRepository')->upsert(
            ['id' => $entity->getId(), 'name' => $entity->getName()],
            ['id'],
            ['name']
        ); // Upsert didukung di PostgreSQL (Laravel 9+)
    }

    public function delete($id): void
    {
        $this->db->table('PackingListRepository')->where('id', $id)->delete();
    }
}
