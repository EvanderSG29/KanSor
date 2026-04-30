<?php

namespace App\Services\PosKantin;

use App\Models\Supplier;

class SupplierSyncPayloadFactory
{
    /**
     * @return array{
     *     id: string,
     *     supplierName: string,
     *     contactName: string,
     *     contactPhone: string,
     *     commissionRate: float,
     *     commissionBaseType: string,
     *     payoutTermDays: int,
     *     notes: string,
     *     isActive: bool
     * }
     */
    public function make(Supplier $supplier): array
    {
        $contactInfo = trim((string) $supplier->contact_info);
        $resolvedContact = $this->resolveContactInfo($contactInfo);

        return [
            'id' => (string) $supplier->getKey(),
            'supplierName' => $supplier->name,
            'contactName' => $resolvedContact['contactName'],
            'contactPhone' => $resolvedContact['contactPhone'],
            'commissionRate' => (float) $supplier->percentage_cut,
            'commissionBaseType' => 'revenue',
            'payoutTermDays' => 0,
            'notes' => $resolvedContact['notes'],
            'isActive' => $supplier->active,
        ];
    }

    /**
     * @return array{contactName: string, contactPhone: string, notes: string}
     */
    private function resolveContactInfo(string $contactInfo): array
    {
        if ($contactInfo === '') {
            return [
                'contactName' => '',
                'contactPhone' => '',
                'notes' => '',
            ];
        }

        if (preg_match('/^(.*?)\s*[\-|\/|,]\s*(\+?[0-9][0-9\s-]{5,})$/', $contactInfo, $matches) === 1) {
            return [
                'contactName' => trim((string) $matches[1]),
                'contactPhone' => trim((string) $matches[2]),
                'notes' => '',
            ];
        }

        if (preg_match('/^(.*?)(\+?[0-9][0-9\s-]{5,})$/', $contactInfo, $matches) === 1) {
            return [
                'contactName' => trim((string) $matches[1], " \t\n\r\0\x0B-/,|"),
                'contactPhone' => trim((string) $matches[2]),
                'notes' => '',
            ];
        }

        if (preg_match('/[0-9]/', $contactInfo) === 1 && preg_match('/[A-Za-z]/', $contactInfo) !== 1) {
            return [
                'contactName' => '',
                'contactPhone' => $contactInfo,
                'notes' => '',
            ];
        }

        if (preg_match('/[0-9]/', $contactInfo) !== 1) {
            return [
                'contactName' => $contactInfo,
                'contactPhone' => '',
                'notes' => '',
            ];
        }

        return [
            'contactName' => '',
            'contactPhone' => '',
            'notes' => $contactInfo,
        ];
    }
}
