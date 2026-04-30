<?php

namespace App\Services\Setup;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class SchemaReadinessService
{
    /**
     * @var array{isLocal: bool, hasPendingMigrations: bool, pendingMigrations: list<string>}|null
     */
    private ?array $statusCache = null;

    public function __construct(
        private Migrator $migrator,
    ) {}

    /**
     * @return array{isLocal: bool, hasPendingMigrations: bool, pendingMigrations: list<string>}
     */
    public function status(): array
    {
        if ($this->statusCache !== null) {
            return $this->statusCache;
        }

        $migrationFiles = $this->migrator->getMigrationFiles(array_unique([
            database_path('migrations'),
            ...$this->migrator->paths(),
        ]));

        $ranMigrations = $this->migrator->repositoryExists()
            ? $this->migrator->getRepository()->getRan()
            : [];

        $pendingMigrations = collect(array_keys($migrationFiles))
            ->reject(fn (string $migrationName): bool => in_array($migrationName, $ranMigrations, true))
            ->values()
            ->all();

        return $this->statusCache = [
            'isLocal' => app()->environment('local'),
            'hasPendingMigrations' => $pendingMigrations !== [],
            'pendingMigrations' => $pendingMigrations,
        ];
    }

    public function shouldBlockApplication(): bool
    {
        $status = $this->status();

        return $status['isLocal'] && $status['hasPendingMigrations'];
    }

    public function hasPendingMigrations(): bool
    {
        return $this->status()['hasPendingMigrations'];
    }

    /**
     * @return list<string>
     */
    public function pendingMigrations(): array
    {
        return $this->status()['pendingMigrations'];
    }

    /**
     * @return array{success: bool, message: string, exitCode: int|null, output: string, pendingMigrations: list<string>}
     */
    public function runPendingMigrations(): array
    {
        if (! app()->environment('local')) {
            return [
                'success' => false,
                'message' => 'Setup migrasi lokal hanya tersedia pada environment local.',
                'exitCode' => null,
                'output' => '',
                'pendingMigrations' => $this->pendingMigrations(),
            ];
        }

        if (! $this->hasPendingMigrations()) {
            return [
                'success' => true,
                'message' => 'Database lokal sudah sinkron. Tidak ada migrasi yang perlu dijalankan.',
                'exitCode' => 0,
                'output' => '',
                'pendingMigrations' => [],
            ];
        }

        try {
            $exitCode = Artisan::call('migrate', [
                '--no-interaction' => true,
            ]);
        } catch (Throwable $throwable) {
            $this->forgetCachedStatus();

            return [
                'success' => false,
                'message' => 'Migrasi lokal gagal dijalankan.',
                'exitCode' => null,
                'output' => trim($throwable->getMessage()),
                'pendingMigrations' => $this->pendingMigrations(),
            ];
        }

        $this->forgetCachedStatus();

        $pendingMigrations = $this->pendingMigrations();
        $success = $exitCode === 0 && $pendingMigrations === [];

        return [
            'success' => $success,
            'message' => $success
                ? 'Migrasi lokal berhasil dijalankan.'
                : 'Migrasi selesai dijalankan, tetapi database lokal masih belum sinkron penuh.',
            'exitCode' => $exitCode,
            'output' => trim(Artisan::output()),
            'pendingMigrations' => $pendingMigrations,
        ];
    }

    private function forgetCachedStatus(): void
    {
        $this->statusCache = null;
    }
}
