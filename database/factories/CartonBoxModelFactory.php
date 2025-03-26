<?php

namespace Database\Factories;

use App\Infrastructure\Models\CartonBoxModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartonBoxModelFactory extends Factory
{
    protected $model = CartonBoxModel::class;

    public function definition()
    {
        return [
            'id' => $this->faker->uuid,
            'barcode' => $this->faker->numberBetween(100, 999),
            'internal_sku' => 'CARTON-' . $this->faker->numberBetween(100, 999),
            'items_quantity' => $this->faker->numberBetween(5, 20),
            'packing_list_id' => null,
            'status' => 'OPEN',
            'validation_status' => 'PENDING',
        ];
    }
}
