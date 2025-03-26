<?php

namespace Database\Factories;

use App\Infrastructure\Models\PackingListModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackingListModelFactory extends Factory
{
    protected $model = PackingListModel::class;

    public function definition()
    {
        return [
            'id' => $this->faker->uuid,
            'purchase_order_number' => $this->faker->unique()->word . $this->faker->numberBetween(100, 999),
            'carton_boxes_quantity' => $this->faker->numberBetween(1, 10),
            'details' => json_encode(['carton_validation_rule' => 'SOLID']),
        ];
    }
}
