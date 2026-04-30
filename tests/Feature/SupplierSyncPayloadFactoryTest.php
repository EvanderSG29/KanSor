<?php

use App\Models\Supplier;
use App\Services\PosKantin\SupplierSyncPayloadFactory;

test('supplier sync payload maps phone-only contact info into contact phone', function () {
    $supplier = new Supplier([
        'name' => 'Supplier Baru',
        'contact_info' => '08123',
        'percentage_cut' => 10,
        'active' => true,
    ]);
    $supplier->id = 123;

    $payload = app(SupplierSyncPayloadFactory::class)->make($supplier);

    expect($payload)->toMatchArray([
        'id' => '123',
        'supplierName' => 'Supplier Baru',
        'contactName' => '',
        'contactPhone' => '08123',
        'commissionRate' => 10.0,
        'commissionBaseType' => 'revenue',
        'payoutTermDays' => 0,
        'notes' => '',
        'isActive' => true,
    ]);
});

test('supplier sync payload splits contact name and phone when contact info is structured', function () {
    $supplier = new Supplier([
        'name' => 'Supplier Baru',
        'contact_info' => 'Pak Latif - 08123 45678',
        'percentage_cut' => 12.5,
        'active' => false,
    ]);
    $supplier->id = 321;

    $payload = app(SupplierSyncPayloadFactory::class)->make($supplier);

    expect($payload)->toMatchArray([
        'id' => '321',
        'supplierName' => 'Supplier Baru',
        'contactName' => 'Pak Latif',
        'contactPhone' => '08123 45678',
        'commissionRate' => 12.5,
        'commissionBaseType' => 'revenue',
        'payoutTermDays' => 0,
        'notes' => '',
        'isActive' => false,
    ]);
});
