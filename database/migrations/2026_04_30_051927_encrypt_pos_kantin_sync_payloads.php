<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->encryptJsonColumn('pos_sync_outbox', 'payload');
        $this->encryptJsonColumn('pos_sync_outbox', 'server_snapshot');
        $this->encryptJsonColumn('pos_sync_conflicts', 'local_payload');
        $this->encryptJsonColumn('pos_sync_conflicts', 'server_payload');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->decryptJsonColumn('pos_sync_outbox', 'payload');
        $this->decryptJsonColumn('pos_sync_outbox', 'server_snapshot');
        $this->decryptJsonColumn('pos_sync_conflicts', 'local_payload');
        $this->decryptJsonColumn('pos_sync_conflicts', 'server_payload');
    }

    private function encryptJsonColumn(string $table, string $column): void
    {
        DB::table($table)
            ->select(['id', $column])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $column): void {
                foreach ($rows as $row) {
                    $value = $row->{$column};

                    if ($value === null) {
                        continue;
                    }

                    if ($this->isEncryptedValue($value)) {
                        continue;
                    }

                    $decodedValue = $this->decodePlainJsonValue($table, (int) $row->id, $column, $value);

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            $column => Crypt::encryptString(
                                json_encode($decodedValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                            ),
                        ]);
                }
            });
    }

    private function decryptJsonColumn(string $table, string $column): void
    {
        DB::table($table)
            ->select(['id', $column])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $column): void {
                foreach ($rows as $row) {
                    $value = $row->{$column};

                    if ($value === null || ! $this->isEncryptedValue($value)) {
                        continue;
                    }

                    $decrypted = Crypt::decryptString($value);
                    $decodedValue = json_decode($decrypted, true);

                    if (! is_array($decodedValue)) {
                        throw new RuntimeException(sprintf(
                            'Kolom %s.%s untuk ID %d tidak berisi JSON array yang valid saat rollback enkripsi.',
                            $table,
                            $column,
                            $row->id,
                        ));
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            $column => json_encode($decodedValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                        ]);
                }
            });
    }

    private function isEncryptedValue(mixed $value): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            $decrypted = Crypt::decryptString($value);
            $decodedValue = json_decode($decrypted, true);

            return is_array($decodedValue);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePlainJsonValue(string $table, int $id, string $column, mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException(sprintf(
                'Kolom %s.%s untuk ID %d tidak dapat dienkripsi karena nilainya kosong atau bukan string JSON.',
                $table,
                $column,
                $id,
            ));
        }

        $decodedValue = json_decode($value, true);

        if (! is_array($decodedValue)) {
            throw new RuntimeException(sprintf(
                'Kolom %s.%s untuk ID %d tidak berisi JSON array yang valid untuk dienkripsi.',
                $table,
                $column,
                $id,
            ));
        }

        return $decodedValue;
    }
};
