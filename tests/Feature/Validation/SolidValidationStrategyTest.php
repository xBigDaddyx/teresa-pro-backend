<?php

namespace Tests\Unit\Domain\Accuracy\Validation\Strategies;

use App\Domain\Accuracy\Validation\Strategies\SolidValidationStrategy;
use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\Validation\Entities\Item;
use Mockery;

test('validate should pass when item attributes match carton attributes', function () {
    $strategy = new SolidValidationStrategy();
    $cartonBox = Mockery::mock(CartonBox::class);
    $item = Mockery::mock(Item::class);

    $cartonAttributes = [
        'Style' => 'ABC123',
        'Size' => 'M',
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];

    $itemDetails = [
        'Style' => 'ABC123',
        'Size' => 'M',
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn(['carton_attributes' => $cartonAttributes]);
    $item->shouldReceive('getDetails')->andReturn($itemDetails);

    expect(fn() => $strategy->validate($cartonBox, $item))->not->toThrow(\Exception::class);
});

test('validate should throw exception when item style does not match carton style', function () {
    // Arrange
    $strategy = new SolidValidationStrategy();

    $cartonBox = Mockery::mock(CartonBox::class);
    $item = Mockery::mock(Item::class);

    $cartonAttributes = [
        'Style' => 'ABC123',
        'Size' => 'M',
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];

    $itemDetails = [
        'Style' => 'XYZ456', // Different style
        'Size' => 'M',
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];

    $cartonBox->shouldReceive('getPackingList->getDetails')
        ->andReturn(['carton_attributes' => $cartonAttributes]);
    $item->shouldReceive('getDetails')->andReturn($itemDetails);

    // Act & Assert
    expect(fn() => $strategy->validate($cartonBox, $item))
        ->toThrow(\Exception::class, 'Attribute Mismatch!');
});

test('validate should throw exception when any attribute does not match', function () {
    // Arrange
    $strategy = new SolidValidationStrategy();

    $cartonBox = Mockery::mock(CartonBox::class);
    $item = Mockery::mock(Item::class);

    $cartonAttributes = [
        'Style' => 'ABC123',
        'Size' => 'M',
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];

    $itemDetails = [
        'Style' => 'ABC123',
        'Size' => 'L', // Different size
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];

    $cartonBox->shouldReceive('getPackingList->getDetails')
        ->andReturn(['carton_attributes' => $cartonAttributes]);
    $item->shouldReceive('getDetails')->andReturn($itemDetails);

    // Act & Assert
    expect(fn() => $strategy->validate($cartonBox, $item))
        ->toThrow(\Exception::class, 'Attribute Mismatch!');
});

test('getAttributes should return carton attributes from packing list', function () {
    $strategy = new SolidValidationStrategy();
    $cartonBox = Mockery::mock(CartonBox::class);
    $attributes = [
        'Style' => 'ABC123',
        'Size' => 'M',
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn(['carton_attributes' => $attributes]);

    $result = $strategy->getAttributes($cartonBox);
    expect($result)->toBe($attributes);
});

test('getAttributes should handle JSON string details', function () {
    $strategy = new SolidValidationStrategy();
    $cartonBox = Mockery::mock(CartonBox::class);
    $attributes = [
        'Style' => 'ABC123',
        'Size' => 'M',
        'Color' => 'Red',
        'Contract' => 'CT001'
    ];
    $details = ['carton_attributes' => $attributes];

    $cartonBox->shouldReceive('getPackingList->getDetails')->andReturn($details);

    $result = $strategy->getAttributes($cartonBox);
    expect($result)->toBe($attributes);
});
