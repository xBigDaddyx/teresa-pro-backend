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

    // Setup tenant database seperti ProcessCartonTest
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

it('can search carton by barcode and process automatically if single result', function () {
    $packingList = PackingListModel::create([
        'purchase_order_number' => 'PO123',
        'carton_boxes_quantity' => 10,
        'details' => json_encode(['carton_validation_rule' => 'SOLID']),
    ]);

    $cartonBox = CartonBoxModel::create([
        'barcode' => '123',
        'internal_sku' => 'CARTON-001',
        'validation_status' => CartonValidationStatus::PENDING,
        'status' => CartonStatus::OPEN,
        'items_quantity' => 10,
        'packing_list_id' => $packingList->id,
    ]);

    $cartonId = $cartonBox->getKey();

    $response = $this->actingAs($this->user)
        ->call('GET', '/api/v1/carton-boxes', ['barcode' => '123'], [], [], [
            'HTTP_X-Tenant-ID' => 'solo',
            'HTTP_Accept' => 'application/vnd.api.v1+json',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Carton retrieved and processed successfully',
            'meta' => [],
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $cartonId,
            'barcode' => '123',
            'internal_sku' => 'CARTON-001',
            'validation_status' => CartonValidationStatus::PROCESS,
            'status' => CartonStatus::OPEN,
            'processed_by' => 1,
        ], 'data.0')
        ->assertJsonStructure([
            'status', 'message', 'data' => [[
                'id', 'barcode', 'internal_sku', 'validation_status', 'status',
                'processed_at', 'processed_by', 'items_quantity', 'buyer', 'next_step'
            ]], 'meta', 'timestamp'
        ])
        ->assertJsonPath('data.0.next_step', fn ($nextStep) => str_contains($nextStep, "/api/v1/carton-boxes/{$cartonId}/validate-item"));

    Event::assertDispatched(CartonProcessed::class, function ($event) use ($response, $cartonId) {
        return $event->carton->getId() === $cartonId &&
            $event->nextStep === $response->json('data.0.next_step');
    });

    $carton = CartonBoxModel::find($cartonId);
    expect($carton->validation_status)->toBe(CartonValidationStatus::PROCESS);
    expect($carton->processed_by)->toBe(1);
});

it('can search carton by barcode and return multiple results without processing', function () {
    $packingList1 = PackingListModel::create([
        'purchase_order_number' => 'PO123',
        'carton_boxes_quantity' => 10,
        'details' => json_encode(['carton_validation_rule' => 'SOLID']),
    ]);

    $packingList2 = PackingListModel::create([
        'purchase_order_number' => 'PO124',
        'carton_boxes_quantity' => 10,
        'details' => json_encode(['carton_validation_rule' => 'SOLID']),
    ]);

    $carton1 = CartonBoxModel::create([
        'barcode' => '123',
        'internal_sku' => 'CARTON-001',
        'validation_status' => CartonValidationStatus::PENDING,
        'status' => CartonStatus::OPEN,
        'items_quantity' => 10,
        'packing_list_id' => $packingList1->id,
    ]);

    $carton2 = CartonBoxModel::create([
        'barcode' => '123',
        'internal_sku' => 'CARTON-002',
        'validation_status' => CartonValidationStatus::PENDING,
        'status' => CartonStatus::OPEN,
        'items_quantity' => 10,
        'packing_list_id' => $packingList2->id,
    ]);

    $carton1Id = $carton1->getKey();
    $carton2Id = $carton2->getKey();

    $response = $this->actingAs($this->user)
        ->call('GET', '/api/v1/carton-boxes', ['barcode' => '123'], [], [], [
            'HTTP_X-Tenant-ID' => 'solo',
            'HTTP_Accept' => 'application/vnd.api.v1+json',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Cartons retrieved successfully',
            'meta' => [],
        ])
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $carton1Id, 'validation_status' => CartonValidationStatus::PENDING, 'processed_by' => null], 'data')
        ->assertJsonFragment(['id' => $carton2Id, 'validation_status' => CartonValidationStatus::PENDING, 'processed_by' => null], 'data')
        ->assertJsonPath('data.0.next_step', null)
        ->assertJsonPath('data.1.next_step', null)
        ->assertJsonStructure([
            'status', 'message', 'data' => ['*' => [
                'id', 'barcode', 'internal_sku', 'validation_status', 'status',
                'processed_at', 'processed_by', 'items_quantity', 'buyer', 'next_step'
            ]], 'meta', 'timestamp'
        ]);

    Event::assertNotDispatched(CartonProcessed::class);
});

it('can search carton by barcode, po, and sku with specific filter', function () {
    $packingList = PackingListModel::create([
        'purchase_order_number' => 'PO123',
        'carton_boxes_quantity' => 10,
        'details' => json_encode(['carton_validation_rule' => 'SOLID']),
    ]);

    $cartonBox1 = CartonBoxModel::create([
        'barcode' => '123',
        'internal_sku' => 'CARTON-001',
        'validation_status' => CartonValidationStatus::PENDING,
        'status' => CartonStatus::OPEN,
        'items_quantity' => 10,
        'packing_list_id' => $packingList->id,
    ]);

    $cartonBox2 = CartonBoxModel::create([
        'barcode' => '123',
        'internal_sku' => 'CARTON-002',
        'validation_status' => CartonValidationStatus::PENDING,
        'status' => CartonStatus::OPEN,
        'items_quantity' => 10,
        'packing_list_id' => $packingList->id,
    ]);

    $carton1Id = $cartonBox1->getKey();

    $response = $this->actingAs($this->user)
        ->call('GET', '/api/v1/carton-boxes', ['barcode' => '123', 'po' => 'PO123', 'sku' => 'CARTON-001'], [], [], [
            'HTTP_X-Tenant-ID' => 'solo',
            'HTTP_Accept' => 'application/vnd.api.v1+json',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Carton retrieved and processed successfully',
            'meta' => [],
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $carton1Id,
            'barcode' => '123',
            'internal_sku' => 'CARTON-001',
            'validation_status' => CartonValidationStatus::PROCESS,
            'processed_by' => 1,
        ], 'data.0')
        ->assertJsonPath('data.0.next_step', fn ($nextStep) => str_contains($nextStep, "/api/v1/carton-boxes/{$carton1Id}/validate-item"))
        ->assertJsonStructure([
            'status', 'message', 'data' => [[
                'id', 'barcode', 'internal_sku', 'validation_status', 'status',
                'processed_at', 'processed_by', 'items_quantity', 'buyer', 'next_step'
            ]], 'meta', 'timestamp'
        ]);

    Event::assertDispatched(CartonProcessed::class, function ($event) use ($carton1Id) {
        return $event->carton->getId() === $carton1Id;
    });
});

it('returns error when no carton found by barcode', function () {
    $response = $this->actingAs($this->user)
        ->call('GET', '/api/v1/carton-boxes', ['barcode' => '999'], [], [], [
            'HTTP_X-Tenant-ID' => 'solo',
            'HTTP_Accept' => 'application/vnd.api.v1+json',
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'status' => 'error',
            'message' => 'Carton not found',
            'errors' => [],
        ])
        ->assertJsonStructure(['status', 'message', 'errors', 'timestamp']);
});

it('rejects unsupported version when searching carton', function () {
    $packingList = PackingListModel::create([
        'purchase_order_number' => 'PO123',
        'carton_boxes_quantity' => 10,
        'details' => json_encode(['carton_validation_rule' => 'SOLID']),
    ]);

    $cartonBox = CartonBoxModel::create([
        'barcode' => '123',
        'internal_sku' => 'CARTON-001',
        'validation_status' => CartonValidationStatus::PENDING,
        'status' => CartonStatus::OPEN,
        'items_quantity' => 10,
        'packing_list_id' => $packingList->id,
    ]);

    $response = $this->actingAs($this->user)
        ->call('GET', '/api/v1/carton-boxes', ['barcode' => '123'], [], [], [
            'HTTP_X-Tenant-ID' => 'solo',
            'HTTP_Accept' => 'application/vnd.api.v3+json',
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unsupported API version',
            'errors' => [],
        ])
        ->assertJsonStructure(['status', 'message', 'errors', 'timestamp']);
});
