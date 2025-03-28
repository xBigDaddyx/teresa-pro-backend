<?php

namespace Tests\Unit\Domain\Accuracy\Validation\Strategies;

use App\Domain\Accuracy\Validation\Strategies\RatioValidationStrategy;
use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\Validation\Entities\Item;
use Mockery;

test('validate should pass when item matches ratio rule and quantity is not exceeded', function () {
    $strategy = new RatioValidationStrategy();
    $cartonBox = Mockery::mock(CartonBox::class);
    $item = Mockery::mock(Item::class);

    $cartonDetails = [
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

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn($cartonDetails);
    $item->shouldReceive('getDetails')->andReturn($itemDetails);
    $cartonBox->shouldReceive('getItems')->andReturn([]);

    expect(fn() => $strategy->validate($cartonBox, $item))->not->toThrow(\Exception::class);
});

test('validate should throw exception when no matching ratio rule is found', function () {
    // Arrange
    $strategy = new RatioValidationStrategy();

    $cartonBox = Mockery::mock(CartonBox::class);
    $item = Mockery::mock(Item::class);

    $cartonDetails = [
        [
            'attributes' => [
                'Style' => 'ABC123',
                'Size' => 'M',
                'Color' => 'Blue',
                'Contract' => 'CT001',
                'Quantity_PCS' => 2
            ]
        ]
    ];

    $itemDetails = [
        'Style' => 'ABC123',
        'Size' => 'M',
        'Color' => 'Red', // Different color
        'Contract' => 'CT001'
    ];

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn($cartonDetails);
    $item->shouldReceive('getDetails')->andReturn($itemDetails);

    // Act & Assert
    expect(fn() => $strategy->validate($cartonBox, $item))
        ->toThrow(\Exception::class, 'Attribute Mismatch!');
});

test('validate should throw exception when quantity exceeds ratio limit', function () {
    // Arrange
    $strategy = new RatioValidationStrategy();

    $cartonBox = Mockery::mock(CartonBox::class);
    $item = Mockery::mock(Item::class);

    $cartonDetails = [
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

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn($cartonDetails);
    $item->shouldReceive('getDetails')->andReturn($itemDetails);
    $cartonBox->shouldReceive('getItems')->andReturn($existingItems);

    // Act & Assert
    expect(fn() => $strategy->validate($cartonBox, $item))
        ->toThrow(\Exception::class, 'Quantity Exceeded!');
});

test('getAttributes should return carton details from packing list', function () {
    // Arrange
    $strategy = new RatioValidationStrategy();

    $cartonBox = Mockery::mock(CartonBox::class);
    $details = [
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

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn($details);

    // Act
    $result = $strategy->getAttributes($cartonBox);

    // Assert
    expect($result)->toBe($details);
});

test('getAttributes should handle JSON string details', function () {
    $strategy = new RatioValidationStrategy();
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
