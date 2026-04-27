<?php

namespace App\Console\Commands;

use App\Exceptions\PosKantinException;
use App\Services\PosKantin\PosKantinClient;
use Illuminate\Console\Command;

class PosKantinMigrateLegacySpreadsheetCommand extends Command
{
    protected $signature = 'pos-kantin:migrate-legacy-spreadsheet
        {--source= : Spreadsheet ID lama yang akan dibaca}
        {--commit : Jalankan overwrite ke spreadsheet target. Tanpa opsi ini, command hanya preview}
        {--include-users : Ikut migrasi sheet users tanpa membawa kredensial lama}
        {--allow-without-backups : Lanjut walau backup Google Drive gagal dibuat}';

    protected $description = 'Preview atau jalankan migrasi spreadsheet legacy POS Kantin ke spreadsheet aktif.';

    /**
     * Execute the console command.
     */
    public function handle(PosKantinClient $posKantinClient): int
    {
        $sourceSpreadsheetId = trim((string) ($this->option('source') ?: config('services.pos_kantin.legacy_spreadsheet_id')));

        if ($sourceSpreadsheetId === '') {
            $this->error('Spreadsheet sumber belum diisi. Pakai --source=... atau set POS_KANTIN_LEGACY_SPREADSHEET_ID.');

            return self::FAILURE;
        }

        $isDryRun = ! $this->option('commit');

        try {
            $result = $posKantinClient->migrateLegacySpreadsheet([
                'sourceSpreadsheetId' => $sourceSpreadsheetId,
                'dryRun' => $isDryRun,
                'includeUsers' => (bool) $this->option('include-users'),
                'allowWithoutBackups' => (bool) $this->option('allow-without-backups'),
            ]);
        } catch (PosKantinException $exception) {
            $this->renderMigrationError($exception);

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Mode: %s | Source: %s | Target: %s',
            $isDryRun ? 'preview' : 'commit',
            $result['sourceSpreadsheet']['name'] ?? $sourceSpreadsheetId,
            $result['targetSpreadsheet']['name'] ?? '-',
        ));

        $this->table(
            ['Sheet', 'Mode', 'Source Rows', 'Target Rows', 'Compatible'],
            collect($result['sheets'] ?? [])
                ->map(function (array $sheet): array {
                    return [
                        $sheet['sheetName'] ?? $sheet['sheetKey'] ?? '-',
                        $sheet['mode'] ?? '-',
                        (string) ($sheet['sourceRowCount'] ?? 0),
                        (string) ($sheet['targetRowCount'] ?? 0),
                        ($sheet['compatible'] ?? false) ? 'yes' : 'no',
                    ];
                })
                ->all(),
        );

        foreach (($result['warnings'] ?? []) as $warning) {
            $this->warn((string) $warning);
        }

        if (! $isDryRun && isset($result['backups']) && is_array($result['backups'])) {
            foreach ($result['backups'] as $label => $backup) {
                if (! is_array($backup)) {
                    continue;
                }

                if (($backup['ok'] ?? false) === true) {
                    $this->info(sprintf('Backup %s: %s', $label, (string) ($backup['url'] ?? $backup['id'] ?? '-')));

                    continue;
                }

                $this->warn(sprintf('Backup %s gagal: %s', $label, (string) ($backup['error'] ?? 'Tidak diketahui')));
            }
        }

        if ($isDryRun) {
            $this->info('Preview selesai. Jalankan ulang dengan --commit jika hasilnya sudah valid.');
        } else {
            $this->info('Migrasi selesai.');
        }

        return self::SUCCESS;
    }

    protected function renderMigrationError(PosKantinException $exception): void
    {
        if ($this->isMissingMigrationAction($exception)) {
            $this->error('Deployment Web App Apps Script yang dipakai POS_KANTIN_API_URL masih versi lama dan belum punya action migrateLegacySpreadsheet.');
            $this->line('Arah perbaikannya: push source apps-script terbaru, lalu update deployment Web App yang sama sebelum jalankan command ini lagi.');

            return;
        }

        $this->error($exception->getMessage());
    }

    protected function isMissingMigrationAction(PosKantinException $exception): bool
    {
        return str_contains($exception->getMessage(), 'Action tidak dikenal: migrateLegacySpreadsheet');
    }
}
