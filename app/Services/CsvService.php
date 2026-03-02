<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use RuntimeException;

class CsvService
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $schemas = [
        'users' => ['id', 'nama', 'username', 'password_hash', 'role'],
        'produk' => ['id', 'nama_produk', 'harga_jual', 'stok', 'harga_beli'],
        'transaksi' => ['id', 'tanggal', 'petugas_id', 'total', 'status'],
        'detail_transaksi' => ['id', 'transaksi_id', 'produk_id', 'qty', 'subtotal'],
        'barang_masuk' => ['id', 'tanggal', 'pemasok_id', 'produk_id', 'qty', 'harga'],
    ];

    public function __construct()
    {
        $this->ensureFilesExist();
        $this->ensureDefaultAdmin();
    }

    public function ensureFilesExist(): void
    {
        if (! is_dir($this->dataDir())) {
            mkdir($this->dataDir(), 0775, true);
        }

        foreach ($this->schemas as $file => $header) {
            $path = $this->dataPath($file);
            if (is_file($path)) {
                continue;
            }

            $handle = fopen($path, 'wb');
            if ($handle === false) {
                throw new RuntimeException("Gagal membuat file CSV: {$path}");
            }

            fputcsv($handle, $header);
            fclose($handle);
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function read(string $file): array
    {
        $this->assertKnownFile($file);

        return $this->withLock($file, false, function (string $path): array {
            if (! is_file($path)) {
                return [];
            }

            $handle = fopen($path, 'rb');
            if ($handle === false) {
                throw new RuntimeException("Gagal membuka file CSV: {$path}");
            }

            $header = fgetcsv($handle);
            if (! is_array($header)) {
                fclose($handle);

                return [];
            }

            $rows = [];
            while (($line = fgetcsv($handle)) !== false) {
                if ($line === [null] || $line === []) {
                    continue;
                }

                $assoc = [];
                foreach ($header as $index => $column) {
                    $assoc[$column] = (string) ($line[$index] ?? '');
                }

                $rows[] = $assoc;
            }

            fclose($handle);

            return $rows;
        });
    }

    /**
     * @param  array<int, array<string, scalar|null>>  $rows
     */
    public function write(string $file, array $rows): void
    {
        $this->assertKnownFile($file);

        $this->withLock($file, true, function (string $path) use ($file, $rows): void {
            $header = $this->schemas[$file];
            $tmpPath = $path.'.tmp';

            $handle = fopen($tmpPath, 'wb');
            if ($handle === false) {
                throw new RuntimeException("Gagal menulis file sementara: {$tmpPath}");
            }

            fputcsv($handle, $header);

            foreach ($rows as $row) {
                $line = [];
                foreach ($header as $column) {
                    $line[] = (string) ($row[$column] ?? '');
                }

                fputcsv($handle, $line);
            }

            fflush($handle);
            fclose($handle);

            if (is_file($path) && ! unlink($path)) {
                @unlink($tmpPath);
                throw new RuntimeException("Gagal menghapus file CSV lama: {$path}");
            }

            if (! rename($tmpPath, $path)) {
                @unlink($tmpPath);
                throw new RuntimeException("Gagal mengganti file CSV: {$path}");
            }
        });
    }

    /**
     * @param  array<string, scalar|null>  $row
     * @return array<string, string>
     */
    public function insert(string $file, array $row): array
    {
        $rows = $this->read($file);
        $row['id'] = (string) $this->nextIdFromRows($rows);

        $rows[] = $this->normalizeRow($file, $row);
        $this->write($file, $rows);

        return $this->normalizeRow($file, $row);
    }

    /**
     * @param  array<string, scalar|null>  $updates
     * @return array<string, string>|null
     */
    public function updateById(string $file, int|string $id, array $updates): ?array
    {
        $rows = $this->read($file);
        $found = null;

        foreach ($rows as $idx => $row) {
            if ((string) ($row['id'] ?? '') !== (string) $id) {
                continue;
            }

            $merged = array_merge($row, $updates);
            $rows[$idx] = $this->normalizeRow($file, $merged);
            $found = $rows[$idx];
            break;
        }

        if ($found === null) {
            return null;
        }

        $this->write($file, $rows);

        return $found;
    }

    public function deleteById(string $file, int|string $id): bool
    {
        $rows = $this->read($file);
        $initialCount = count($rows);

        $filtered = array_values(array_filter($rows, function (array $row) use ($id): bool {
            return (string) ($row['id'] ?? '') !== (string) $id;
        }));

        if (count($filtered) === $initialCount) {
            return false;
        }

        $this->write($file, $filtered);

        return true;
    }

    /**
     * @return array<string, string>|null
     */
    public function findOne(string $file, string $column, string $value): ?array
    {
        foreach ($this->read($file) as $row) {
            if (($row[$column] ?? null) === $value) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function where(string $file, string $column, string $value): array
    {
        return array_values(array_filter($this->read($file), function (array $row) use ($column, $value): bool {
            return ($row[$column] ?? null) === $value;
        }));
    }

    public function nextId(string $file): int
    {
        return $this->nextIdFromRows($this->read($file));
    }

    public function dataPath(string $file): string
    {
        return $this->dataDir().DIRECTORY_SEPARATOR.$file.'.csv';
    }

    private function dataDir(): string
    {
        return env('CSV_DATA_DIR', storage_path('app/data'));
    }

    private function ensureDefaultAdmin(): void
    {
        $users = $this->read('users');
        if ($users !== []) {
            return;
        }

        $this->insert('users', [
            'nama' => 'Administrator',
            'username' => 'admin',
            'password_hash' => Hash::make('admin123'),
            'role' => 'admin',
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function nextIdFromRows(array $rows): int
    {
        $maxId = 0;
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > $maxId) {
                $maxId = $id;
            }
        }

        return $maxId + 1;
    }

    /**
     * @param  array<string, scalar|null>  $row
     * @return array<string, string>
     */
    private function normalizeRow(string $file, array $row): array
    {
        $normalized = [];
        foreach ($this->schemas[$file] as $column) {
            $normalized[$column] = (string) ($row[$column] ?? '');
        }

        return $normalized;
    }

    /**
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    private function withLock(string $file, bool $exclusive, callable $callback): mixed
    {
        $path = $this->dataPath($file);
        $lockPath = $this->dataPath($file.'.lock');

        $lockHandle = fopen($lockPath, 'cb');
        if ($lockHandle === false) {
            throw new RuntimeException("Gagal membuat lock file: {$lockPath}");
        }

        $lockMode = $exclusive ? LOCK_EX : LOCK_SH;
        if (! flock($lockHandle, $lockMode)) {
            fclose($lockHandle);
            throw new RuntimeException("Gagal mengunci file: {$lockPath}");
        }

        try {
            return $callback($path);
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    private function assertKnownFile(string $file): void
    {
        if (! array_key_exists($file, $this->schemas)) {
            throw new RuntimeException("File CSV tidak dikenal: {$file}");
        }
    }
}
