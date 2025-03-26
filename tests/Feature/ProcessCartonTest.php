<?php

use App\Events\CartonProcessed;
use App\Infrastructure\Models\CartonBoxModel;
use App\Infrastructure\Models\PackingListModel;
use App\Enums\CartonValidationStatus;
use App\Enums\CartonStatus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\TestCase;



beforeEach(function () {
    Event::fake();
    $this->user = new class extends \Illuminate\Foundation\Auth\User {
        public function getAuthIdentifier() { return 1; }
    };

    $this->tenantDatabase = 'tenant_test_' . uniqid();
    DB::purge('tenant');
    DB::disconnect('tenant');
    DB::setDefaultConnection('pgsql');

    DB::connection('pgsql')->statement("SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '{$this->tenantDatabase}' AND pid <> pg_backend_pid();");
    DB::connection('pgsql')->statement("DROP DATABASE IF EXISTS {$this->tenantDatabase}");
    DB::connection('pgsql')->statement("CREATE DATABASE {$this->tenantDatabase}");

    config(['database.connections.tenant' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => $this->tenantDatabase,
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8',
        'schema' => 'public',
        'sslmode' => env('DB_SSLMODE', 'prefer'),
    ]]);

    DB::setDefaultConnection('tenant');
    Artisan::call('migrate', ['--database' => 'tenant']);
    \App\Infrastructure\Tenancy\TenantManager::setCurrent(['database' => $this->tenantDatabase]);
});

afterEach(function () {
    DB::purge('tenant');
    DB::disconnect('tenant');
    DB::setDefaultConnection('pgsql');

    DB::connection('pgsql')->statement("SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '{$this->tenantDatabase}' AND pid <> pg_backend_pid();");
    DB::connection('pgsql')->statement("DROP DATABASE IF EXISTS {$this->tenantDatabase}");
    \App\Infrastructure\Tenancy\TenantManager::setCurrent(null);
});

it('can process a carton manually with v1 prefix and header', function () {
    $packingList = PackingListModel::create([
        'purchase_order_number' => 'PO123',
        'carton_boxes_quantity' => 10,
        'details' => json_encode(['carton_validation_rule' => 'SOLID']),
    ]);

    $cartonBox = CartonBoxModel::create([
        'barcode' => '123',
        'internal_sku' => 'CARTON-001',
        'validation_status' => CartonValidationStatus::PENDING->value,
        'status' => CartonStatus::OPEN->value,
        'items_quantity' => 10,
        'packing_list_id' => $packingList->id,
    ]);

    $cartonId = $cartonBox->getKey();

    $response = $this->actingAs($this->user)
        ->call('POST', "/api/v1/carton-boxes/{$cartonId}/process", [], [], [], [
            'HTTP_X-Tenant-ID' => 'solo',
            'HTTP_Accept' => 'application/vnd.api.v1+json',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => ['id', 'barcode', 'internal_sku', 'validation_status', 'status', 'processed_at', 'processed_by', 'items_quantity', 'buyer', 'next_step'],
            'meta',
            'timestamp',
        ])
        ->assertJson([
            'status' => 'success',
            'message' => 'Carton processed successfully',
        ])
        ->assertJsonFragment([
            'id' => $cartonId,
            'barcode' => '123',
            'internal_sku' => 'CARTON-001',
            'validation_status' => CartonValidationStatus::PROCESS->value,
            'status' => CartonStatus::OPEN->value,
            'processed_by' => 1,
        ], 'data')
        ->assertJsonPath('data.next_step', fn ($nextStep) => str_contains($nextStep, "/api/v1/carton-boxes/{$cartonId}/validate-item"));

    $response->assertJson(['meta' => []]);

    Event::assertDispatched(CartonProcessed::class, function ($event) use ($cartonId, $response) {
        return $event->carton->getId() === $cartonId &&
            $event->nextStep === $response->json('data.next_step');
    });

    $carton = CartonBoxModel::find($cartonId);
    expect($carton->validation_status)->toBe(CartonValidationStatus::PROCESS);
    expect($carton->processed_by)->toBe(1);
    expect($carton->processed_at)->not->toBeNull();
});

it('returns error when processing non-existent carton with v1 versioning', function () {
    $response = $this->actingAs($this->user)
        ->call('POST', '/api/v1/carton-boxes/non-existent-id/process', [], [], [], [
            'HTTP_X-Tenant-ID' => 'solo',
            'HTTP_Accept' => 'application/vnd.api.v1+json',
        ]);

    $response->assertStatus(404)
        ->assertJsonStructure(['status', 'message', 'errors', 'timestamp'])
        ->assertJson([
            'status' => 'error',
            'message' => 'Carton not found',
            'errors' => [],
        ]);
});

it('rejects unsupported version when processing carton', function () {
    $packingList = PackingListModel::create([
        'purchase_order_number' => 'PO123',
        'carton_boxes_quantity' => 10,
        'details' => json_encode(['carton_validation_rule' => 'SOLID']),
    ]);

    $cartonBox = CartonBoxModel::create([
        'barcode' => '123',
        'internal_sku' => 'CARTON-001',
        'validation_status' => CartonValidationStatus::PENDING->value,
        'status' => CartonStatus::OPEN->value,
        'items_quantity' => 10,
        'packing_list_id' => $packingList->id,
    ]);

    $cartonId = $cartonBox->getKey();

    $response = $this->actingAs($this->user)
        ->call('POST', "/api/v1/carton-boxes/{$cartonId}/process", [], [], [], [
            'HTTP_X-Tenant-ID' => 'solo',
            'HTTP_Accept' => 'application/vnd.api.v3+json',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure(['status', 'message', 'errors', 'timestamp'])
        ->assertJson([
            'status' => 'error',
            'message' => 'Unsupported API version',
            'errors' => [],
        ]);
});
