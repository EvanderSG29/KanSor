<?php

use App\Models\Food;
use App\Services\PosKantin\FoodSyncPayloadFactory;

test('food sync payload maps local food into apps script save food payload', function () {
    $food = new Food([
        'supplier_id' => 77,
        'name' => 'Bakwan Jagung',
        'unit' => 'pcs',
        'default_price' => 3500,
        'active' => true,
    ]);
    $food->id = 123;

    $payload = app(FoodSyncPayloadFactory::class)->make($food);

    expect($payload)->toMatchArray([
        'id' => '123',
        'supplierId' => '77',
        'name' => 'Bakwan Jagung',
        'unit' => 'pcs',
        'defaultPrice' => 3500,
        'isActive' => true,
    ]);
});

test('food sync payload normalizes missing default price into zero', function () {
    $food = new Food([
        'supplier_id' => 88,
        'name' => 'Es Teh',
        'unit' => 'gelas',
        'default_price' => null,
        'active' => false,
    ]);
    $food->id = 456;

    $payload = app(FoodSyncPayloadFactory::class)->make($food);

    expect($payload)->toMatchArray([
        'id' => '456',
        'supplierId' => '88',
        'name' => 'Es Teh',
        'unit' => 'gelas',
        'defaultPrice' => 0,
        'isActive' => false,
    ]);
});
