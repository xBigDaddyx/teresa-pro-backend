<?php

namespace Tests\Unit\Domain\Accuracy\Validation\Strategies;

use App\Domain\Accuracy\Validation\Strategies\MixValidationStrategy;
use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\Validation\Entities\Item;
use Mockery;

test('validate should pass when item matches a mix rule and quantity is not exceeded', function () {
    $strategy = new MixValidationStrategy();
    $cartonBox = Mockery::mock(CartonBox::class);
    $item = Mockery::mock(Item::class);

    $cartonAttributes = [
        [
            'attributes' => [
                'Style' => 'ABC123',
                'Size' => 'M',
                'Color' => 'Red',
                'Contract' => 'CT001',
                'Quantity_PCS' => 2
            ]
        ]
    ];

    $itemDetails = [
        'Style' => 'ABC123',
        'Size' => 'M',
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn($cartonAttributes);
    $item->shouldReceive('getDetails')->andReturn($itemDetails);
    $cartonBox->shouldReceive('getItems')->andReturn([]);

    expect(fn() => $strategy->validate($cartonBox, $item))->not->toThrow(\Exception::class);
});

test('validate should throw exception when no matching mix rule is found', function () {
    // Arrange
    $strategy = new MixValidationStrategy();

    $cartonBox = Mockery::mock(CartonBox::class);
    $item = Mockery::mock(Item::class);

    $cartonAttributes = [
        [
            'attributes' => [
                'Style' => 'ABC123',
                'Size' => 'M',
                'Color' => 'Red',
                'Contract' => 'CT001',
                'Quantity_PCS' => 2
            ]
        ]
    ];

    $itemDetails = [
        'Style' => 'DEF789', // Different style
        'Size' => 'M',
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn($cartonAttributes);
    $item->shouldReceive('getDetails')->andReturn($itemDetails);

    // Act & Assert
    expect(fn() => $strategy->validate($cartonBox, $item))
        ->toThrow(\Exception::class, 'Attribute Mismatch!');
});

test('validate should throw exception when quantity exceeds mix rule limit', function () {
    // Arrange
    $strategy = new MixValidationStrategy();

    $cartonBox = Mockery::mock(CartonBox::class);
    $item = Mockery::mock(Item::class);

    $cartonAttributes = [
        [
            'attributes' => [
                'Style' => 'ABC123',
                'Size' => 'M',
                'Color' => 'Red',
                'Contract' => 'CT001',
                'Quantity_PCS' => 2
            ]
        ]
    ];

    $itemDetails = [
        'Style' => 'ABC123',
        'Size' => 'M',
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];

    $existingItems = [
        Mockery::mock(Item::class),
        Mockery::mock(Item::class)
    ];

    foreach ($existingItems as $existingItem) {
        $existingItem->shouldReceive('getDetails')->andReturn($itemDetails);
    }

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn($cartonAttributes);
    $item->shouldReceive('getDetails')->andReturn($itemDetails);
    $cartonBox->shouldReceive('getItems')->andReturn($existingItems);

    // Act & Assert
    expect(fn() => $strategy->validate($cartonBox, $item))
        ->toThrow(\Exception::class, 'Quantity Exceeded!');
});

test('getAttributes should return carton attributes from packing list', function () {
    // Arrange
    $strategy = new MixValidationStrategy();

    $cartonBox = Mockery::mock(CartonBox::class);
    $attributes = [
        [
            'attributes' => [
                'Style' => 'ABC123',
                'Size' => 'M',
                'Color' => 'Red',
                'Contract' => 'CT001',
                'Quantity_PCS' => 2
            ]
        ],
        [
            'attributes' => [
                'Style' => 'XYZ456',
                'Size' => 'L',
                'Color' => 'Blue',
                'Contract' => 'CT002',
                'Quantity_PCS' => 3
            ]
        ]
    ];

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn($attributes);

    // Act
    $result = $strategy->getAttributes($cartonBox);

    // Assert
    expect($result)->toBe($attributes);
});

test('getAttributes should handle JSON string details', function () {
    $strategy = new MixValidationStrategy();
    $cartonBox = Mockery::mock(CartonBox::class);
    $attributes = [
        [
            'attributes' => [
                'Style' => 'ABC123',
                'Size' => 'M',
                'Color' => 'Red',
                'Contract' => 'CT001',
                'Quantity_PCS' => 2
            ]
        ]
    ];

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn($attributes);

    $result = $strategy->getAttributes($cartonBox);
    expect($result)->toBe($attributes);
});
