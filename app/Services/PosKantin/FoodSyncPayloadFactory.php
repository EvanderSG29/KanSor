<?php

namespace App\Services\PosKantin;

use App\Models\Food;

class FoodSyncPayloadFactory
{
    /**
     * @return array{
     *     id: string,
     *     supplierId: string,
     *     name: string,
     *     unit: string,
     *     defaultPrice: int,
     *     isActive: bool
     * }
     */
    public function make(Food $food): array
    {
        return [
            'id' => (string) $food->getKey(),
            'supplierId' => (string) $food->supplier_id,
            'name' => $food->name,
            'unit' => $food->unit,
            'defaultPrice' => (int) ($food->default_price ?? 0),
            'isActive' => $food->active,
        ];
    }
}
