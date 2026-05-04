<?php

use App\Models\AuditLog;
use App\Models\Food;
use App\Models\PosKantinSyncConflict;
use App\Models\PosKantinSyncOutbox;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\PosKantin\PosKantinSyncService;
use App\Services\PosKantin\SaleTransactionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.kansor.api_url' => null,
    ]);

    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response('', 200),
    ]);

    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);

    $this->petugas = User::factory()->create([
        'role' => User::ROLE_PETUGAS,
        'status' => User::STATUS_ACTIVE,
        'active' => true,
    ]);
});

test('admin can view audit logs and petugas cannot access the audit page', function () {
    AuditLog::factory()->create([
        'actor_user_id' => $this->admin->id,
        'action' => 'sale.updated',
        'subject_type' => Sale::class,
        'subject_id' => '99',
        'metadata' => ['status' => 'logged'],
    ]);

    $this->actingAs($this->admin)
        ->get(route('kansor.admin.audit-logs.index'))
        ->assertSuccessful()
        ->assertSee('Audit Aktivitas')
        ->assertSee('sale.updated');

    $this->actingAs($this->petugas)
        ->get(route('kansor.admin.audit-logs.index'))
        ->assertForbidden();
});

test('audit logger redacts sync payload keys from metadata', function () {
    $request = Request::create('/kansor/sinkronisasi', 'POST');
    $request->setUserResolver(fn () => $this->admin);

    $auditLog = app(AuditLogger::class)->log(
        $request,
        'sync.payload.checked',
        metadata: [
            'payload' => ['supplierName' => 'Supplier Rahasia'],
            'local_payload' => ['supplierName' => 'Lokal Rahasia'],
            'server_snapshot' => ['supplierName' => 'Server Rahasia'],
            'token' => 'token-rahasia',
            'context' => 'safe-value',
        ],
    );

    expect($auditLog->metadata)->toBe([
        'context' => 'safe-value',
    ]);

    $rawMetadata = DB::table('audit_logs')->where('id', $auditLog->id)->value('metadata');

    expect($rawMetadata)->toBeString()
        ->not->toContain('Supplier Rahasia')
        ->not->toContain('Lokal Rahasia')
        ->not->toContain('Server Rahasia')
        ->not->toContain('token-rahasia');
});

test('supplier payment confirmation writes an encrypted audit log entry', function () {
    $supplier = Supplier::factory()->create([
        'active' => true,
    ]);

    $sale = Sale::factory()->create([
        'supplier_id' => $supplier->id,
        'user_id' => $this->petugas->id,
    ]);

    $this->actingAs($this->admin)
        ->from(route('kansor.admin.sales.show', $sale))
        ->patch(route('kansor.admin.sales.confirm-supplier-paid', $sale), [
            'paid_at' => '2026-04-30',
            'paid_amount' => 18000,
            'taken_note' => 'Catatan pemasok yang sensitif',
        ])
        ->assertRedirect(route('kansor.admin.sales.show', $sale))
        ->assertSessionHas('status');

    $auditLog = AuditLog::query()
        ->where('action', 'sale.supplier_payment_confirmed')
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog?->actor_user_id)->toBe($this->admin->id)
        ->and($auditLog?->subject_type)->toBe(Sale::class)
        ->and($auditLog?->subject_id)->toBe((string) $sale->id)
        ->and($auditLog?->metadata)->toMatchArray([
            'status_i' => Sale::STATUS_SUPPLIER_PAID,
            'supplier_paid_at' => '2026-04-30',
            'supplier_paid_amount' => 18000,
            'note_present' => true,
        ]);

    $rawMetadata = DB::table('audit_logs')
        ->where('id', $auditLog?->id)
        ->value('metadata');

    expect($rawMetadata)->toBeString()
        ->not->toContain('Catatan pemasok yang sensitif');
});

test('canteen deposit confirmation writes an audit log entry', function () {
    $supplier = Supplier::factory()->create([
        'active' => true,
    ]);

    $sale = Sale::factory()->create([
        'supplier_id' => $supplier->id,
        'user_id' => $this->petugas->id,
        'total_canteen' => 2500,
    ]);

    $this->actingAs($this->admin)
        ->from(route('kansor.admin.sales.show', $sale))
        ->patch(route('kansor.admin.sales.confirm-canteen-deposited', $sale), [
            'paid_at' => '2026-04-30',
            'paid_amount' => 2500,
            'taken_note' => 'Setor kas akhir hari',
        ])
        ->assertRedirect(route('kansor.admin.sales.show', $sale))
        ->assertSessionHas('status');

    $auditLog = AuditLog::query()
        ->where('action', 'sale.canteen_deposit_confirmed')
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog?->actor_user_id)->toBe($this->admin->id)
        ->and($auditLog?->subject_type)->toBe(Sale::class)
        ->and($auditLog?->subject_id)->toBe((string) $sale->id)
        ->and($auditLog?->metadata)->toMatchArray([
            'status_ii' => Sale::STATUS_CANTEEN_DEPOSITED,
            'canteen_deposited_at' => '2026-04-30',
            'canteen_deposited_amount' => 2500,
            'note_present' => true,
        ]);
});

test('sale update and delete actions write audit logs', function () {
    $transactionSyncService = Mockery::mock(SaleTransactionSyncService::class);
    $transactionSyncService->shouldReceive('syncSale')
        ->once()
        ->andReturn([
            'status' => 'queued',
            'message' => 'Disimpan ke antrean sinkronisasi.',
            'warning' => null,
        ]);
    $transactionSyncService->shouldReceive('deleteSaleItems')
        ->once()
        ->andReturn([
            'status' => 'queued',
            'message' => 'Penghapusan masuk antrean sinkronisasi.',
            'warning' => null,
        ]);

    $this->app->instance(SaleTransactionSyncService::class, $transactionSyncService);

    $supplier = Supplier::factory()->create([
        'active' => true,
        'percentage_cut' => 10,
    ]);
    $food = Food::factory()->create([
        'supplier_id' => $supplier->id,
        'active' => true,
    ]);
    $sale = Sale::factory()->create([
        'supplier_id' => $supplier->id,
        'user_id' => $this->petugas->id,
        'date' => '2026-04-30',
        'total_supplier' => 9000,
        'total_canteen' => 1000,
    ]);
    $saleItem = $sale->items()->create([
        'food_id' => $food->id,
        'unit' => 'pcs',
        'quantity' => 2,
        'leftover' => 0,
        'price_per_unit' => 5000,
        'total_item' => 10000,
        'cut_amount' => 1000,
    ]);

    $this->actingAs($this->admin)
        ->put(route('kansor.admin.sales.update', $sale), [
            'date' => '2026-04-30',
            'supplier_id' => $supplier->id,
            'additional_users' => [],
            'items' => [[
                'id' => $saleItem->id,
                'food_id' => $food->id,
                'unit' => 'pcs',
                'quantity' => 3,
                'leftover' => 1,
                'price_per_unit' => 5000,
            ]],
        ])
        ->assertRedirect(route('kansor.admin.sales.show', $sale))
        ->assertSessionHas('status');

    $saleUpdatedLog = AuditLog::query()
        ->where('action', 'sale.updated')
        ->latest('id')
        ->first();

    expect($saleUpdatedLog)->not->toBeNull()
        ->and($saleUpdatedLog?->subject_id)->toBe((string) $sale->id)
        ->and($saleUpdatedLog?->metadata['before']['item_count'] ?? null)->toBe(1)
        ->and($saleUpdatedLog?->metadata['after']['item_count'] ?? null)->toBe(1)
        ->and($saleUpdatedLog?->metadata['after']['total_supplier'] ?? null)->toBe(13500)
        ->and($saleUpdatedLog?->metadata['after']['total_canteen'] ?? null)->toBe(1500);

    $this->actingAs($this->admin)
        ->delete(route('kansor.admin.sales.destroy', $sale))
        ->assertRedirect(route('kansor.admin.sales.index'))
        ->assertSessionHas('status');

    $saleDeletedLog = AuditLog::query()
        ->where('action', 'sale.deleted')
        ->latest('id')
        ->first();

    expect($saleDeletedLog)->not->toBeNull()
        ->and($saleDeletedLog?->actor_user_id)->toBe($this->admin->id)
        ->and($saleDeletedLog?->subject_type)->toBe(Sale::class)
        ->and($saleDeletedLog?->subject_id)->toBe((string) $sale->id)
        ->and($saleDeletedLog?->metadata['item_count'] ?? null)->toBe(1);
});

test('sync conflict discard and resend write audit logs', function () {
    $syncService = Mockery::mock(PosKantinSyncService::class);
    $syncService->shouldReceive('discardOutbox')
        ->once()
        ->withArgs(fn (User $user, int $outboxId): bool => $user->is($this->petugas) && $outboxId > 0);
    $syncService->shouldReceive('resendOutbox')
        ->once()
        ->withArgs(fn (User $user, int $outboxId): bool => $user->is($this->petugas) && $outboxId > 0);
    $syncService->shouldReceive('sync')
        ->once()
        ->withArgs(fn (User $user, string $trigger): bool => $user->is($this->petugas) && $trigger === 'resend')
        ->andReturn([
            'ok' => true,
            'runId' => 1,
            'summary' => [],
        ]);

    $this->app->instance(PosKantinSyncService::class, $syncService);

    $outbox = PosKantinSyncOutbox::query()->create([
        'scope_owner_user_id' => $this->petugas->id,
        'client_mutation_id' => '11111111-1111-1111-1111-111111111111',
        'action' => 'saveSupplier',
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-001',
        'payload' => ['id' => 'SUP-001'],
        'status' => 'conflict',
        'server_snapshot' => ['id' => 'SUP-001', 'updatedAt' => '2026-04-30T10:00:00.000Z'],
    ]);

    $conflict = PosKantinSyncConflict::query()->create([
        'scope_owner_user_id' => $this->petugas->id,
        'outbox_id' => $outbox->id,
        'entity_type' => 'supplier',
        'entity_remote_id' => 'SUP-001',
        'local_payload' => ['id' => 'SUP-001', 'supplierName' => 'Lokal'],
        'server_payload' => ['id' => 'SUP-001', 'supplierName' => 'Server'],
        'resolution_status' => 'unresolved',
    ]);

    $this->actingAs($this->petugas)
        ->from(route('kansor.sync.index'))
        ->post(route('kansor.sync.outbox.discard', $outbox->id))
        ->assertRedirect(route('kansor.sync.index'))
        ->assertSessionHas('status');

    $discardLog = AuditLog::query()
        ->where('action', 'sync.conflict.resolved_with_server')
        ->latest('id')
        ->first();

    expect($discardLog)->not->toBeNull()
        ->and($discardLog?->subject_type)->toBe(PosKantinSyncConflict::class)
        ->and($discardLog?->subject_id)->toBe((string) $conflict->id)
        ->and($discardLog?->metadata)->toMatchArray([
            'outbox_id' => $outbox->id,
            'entity_type' => 'supplier',
            'entity_remote_id' => 'SUP-001',
            'previous_resolution_status' => 'unresolved',
            'has_server_snapshot' => true,
        ]);

    $this->actingAs($this->petugas)
        ->from(route('kansor.sync.index'))
        ->post(route('kansor.sync.outbox.resend', $outbox->id))
        ->assertRedirect(route('kansor.sync.index'))
        ->assertSessionHas('status');

    $resendLog = AuditLog::query()
        ->where('action', 'sync.conflict.retry_local')
        ->latest('id')
        ->first();

    expect($resendLog)->not->toBeNull()
        ->and($resendLog?->subject_type)->toBe(PosKantinSyncConflict::class)
        ->and($resendLog?->subject_id)->toBe((string) $conflict->id)
        ->and($resendLog?->metadata)->toMatchArray([
            'outbox_id' => $outbox->id,
            'entity_type' => 'supplier',
            'entity_remote_id' => 'SUP-001',
            'previous_resolution_status' => 'unresolved',
        ]);
});

test('user lifecycle actions write audit logs without storing plaintext password data', function () {
    $this->actingAs($this->admin)
        ->post(route('kansor.admin.users.store'), [
            'name' => 'Petugas Audit',
            'email' => 'petugas-audit@example.com',
            'password' => 'KanSor!Pass123',
            'password_confirmation' => 'KanSor!Pass123',
            'role' => User::ROLE_PETUGAS,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.users.index'));

    $managedUser = User::query()->where('email', 'petugas-audit@example.com')->firstOrFail();

    $createdLog = AuditLog::query()
        ->where('action', 'user.created')
        ->latest('id')
        ->first();

    expect($createdLog)->not->toBeNull()
        ->and($createdLog?->subject_type)->toBe(User::class)
        ->and($createdLog?->subject_id)->toBe((string) $managedUser->id)
        ->and($createdLog?->metadata)->toMatchArray([
            'role' => User::ROLE_PETUGAS,
            'active' => true,
            'status' => User::STATUS_ACTIVE,
        ]);

    $this->actingAs($this->admin)
        ->put(route('kansor.admin.users.update', $managedUser), [
            'name' => 'Petugas Audit Update',
            'email' => 'petugas-audit@example.com',
            'password' => 'KanSor!Pass456',
            'password_confirmation' => 'KanSor!Pass456',
            'role' => User::ROLE_ADMIN,
            'active' => '1',
        ])
        ->assertRedirect(route('kansor.admin.users.index'));

    $updatedLog = AuditLog::query()
        ->where('action', 'user.updated')
        ->latest('id')
        ->first();

    expect($updatedLog)->not->toBeNull()
        ->and($updatedLog?->metadata['before']['role'] ?? null)->toBe(User::ROLE_PETUGAS)
        ->and($updatedLog?->metadata['after']['role'] ?? null)->toBe(User::ROLE_ADMIN)
        ->and($updatedLog?->metadata['password_changed'] ?? null)->toBeTrue();

    $rawUpdatedMetadata = DB::table('audit_logs')
        ->where('id', $updatedLog?->id)
        ->value('metadata');

    expect($rawUpdatedMetadata)->toBeString()
        ->not->toContain('KanSor!Pass456');

    $this->actingAs($this->admin)
        ->delete(route('kansor.admin.users.destroy', $managedUser))
        ->assertRedirect(route('kansor.admin.users.index'));

    $deactivatedLog = AuditLog::query()
        ->where('action', 'user.deactivated')
        ->latest('id')
        ->first();

    expect($deactivatedLog)->not->toBeNull()
        ->and($deactivatedLog?->subject_id)->toBe((string) $managedUser->id)
        ->and($deactivatedLog?->metadata['active'] ?? null)->toBeFalse()
        ->and($deactivatedLog?->metadata['status'] ?? null)->toBe(User::STATUS_INACTIVE);
});

