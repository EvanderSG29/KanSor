<?php

use App\Models\Food;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PosKantin\SaleTransactionSyncPayloadFactory;

test('sale transaction sync payload maps a sale item into remote transaction payload', function () {
    $user = User::factory()->make(['name' => 'Petugas Satu']);
    $user->id = 12;
    $supplier = Supplier::factory()->make(['name' => 'Supplier A']);
    $supplier->id = 34;
    $food = new Food([
        'supplier_id' => $supplier->id,
        'name' => 'Bakwan',
        'unit' => 'pcs',
        'default_price' => 5000,
        'active' => true,
    ]);
    $food->id = 56;

    $sale = new Sale([
        'date' => '2026-04-29',
        'additional_users' => [98, 99],
        'status_i' => Sale::STATUS_SUPPLIER_PAID,
        'status_ii' => Sale::STATUS_CANTEEN_DEPOSITED,
        'supplier_paid_at' => '2026-04-30',
        'supplier_paid_amount' => 45000,
        'supplier_payment_note' => 'Bayar setengah',
        'canteen_deposited_at' => '2026-04-30',
        'canteen_deposited_amount' => 5000,
        'canteen_deposit_note' => 'Setor kas',
    ]);
    $sale->id = 78;
    $sale->user_id = $user->id;
    $sale->supplier_id = $supplier->id;
    $sale->setRelation('user', $user);
    $sale->setRelation('supplier', $supplier);

    $item = new SaleItem([
        'food_id' => $food->id,
        'unit' => 'pcs',
        'quantity' => 10,
        'leftover' => 2,
        'price_per_unit' => 5000,
        'total_item' => 50000,
        'cut_amount' => 5000,
    ]);
    $item->id = 91;
    $item->setRelation('food', $food);

    $payload = app(SaleTransactionSyncPayloadFactory::class)->make($sale, $item);

    expect($payload)->toMatchArray([
        'id' => 'SALEITEM-91',
        'transactionDate' => '2026-04-29',
        'inputByUserId' => '12',
        'inputByName' => 'Petugas Satu',
        'supplierId' => '34',
        'foodId' => '56',
        'itemName' => 'Bakwan',
        'unitName' => 'pcs',
        'quantity' => 10,
        'remainingQuantity' => 2,
        'soldQuantity' => 8,
        'unitPrice' => 5000,
        'grossSales' => 50000,
        'totalValue' => 50000,
        'profitAmount' => 5000,
        'commissionAmount' => 5000,
        'supplierNetAmount' => 45000,
    ])
        ->and($payload['notes'])->toContain('saleId=78')
        ->and($payload['notes'])->toContain('statusI=dibayar')
        ->and($payload['notes'])->toContain('statusII=disetor')
        ->and($payload['notes'])->toContain('additionalUsers=98,99')
        ->and($payload['notes'])->toContain('supplierPaidAmount=45000')
        ->and($payload['notes'])->toContain('supplierPaymentNote=Bayar setengah')
        ->and($payload['notes'])->toContain('canteenDepositedAmount=5000')
        ->and($payload['notes'])->toContain('canteenDepositNote=Setor kas');
});
