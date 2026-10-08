<?php

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryPhoto;
use App\Models\MaterialStock;
use App\Models\Site;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('inventory item is school-bound with FR-28 status set', function () {
    $item = InventoryItem::factory()->create();

    expect($item->site)->toBeInstanceOf(Site::class)
        ->and($item->status)->toBe('dipakai')
        ->and(InventoryItem::STATUSES)->toBe(['dipakai', 'cadangan', 'rusak', 'dikembalikan', 'hilang']);
});

test('damaged and lost items carry a reason', function () {
    $damaged = InventoryItem::factory()->damaged()->create();
    $lost = InventoryItem::factory()->lost()->create();

    expect($damaged->status)->toBe('rusak')
        ->and($damaged->status_reason)->not->toBeNull()
        ->and($lost->status)->toBe('hilang')
        ->and($lost->status_reason)->not->toBeNull();
});

test('deleting an item cascades to its photos', function () {
    $item = InventoryItem::factory()->create();
    $photo = InventoryPhoto::factory()->create(['inventory_item_id' => $item->id]);

    $item->delete();

    expect(InventoryPhoto::find($photo->id))->toBeNull();
});

test('material stock balance can never go negative', function () {
    $stock = MaterialStock::factory()->create(['balance' => 3]);
    $stockId = $stock->id;

    config(['database.connections.probe' => config('database.connections.pgsql')]);
    DB::commit();

    try {
        DB::connection('probe')->table('material_stocks')->where('id', $stockId)->update(['balance' => -1]);
        $this->fail('Negative balance accepted');
    } catch (Throwable $e) {
        expect($e->getMessage())->toContain('material_stocks_balance_non_negative');
    }

    expect(MaterialStock::find($stockId)->balance)->toBe(3);
});

test('material stock is unique per school per material', function () {
    $site = Site::factory()->create();
    MaterialStock::factory()->create(['site_id' => $site->id, 'name' => 'kabel UTI']);

    expect(fn () => MaterialStock::factory()->create(['site_id' => $site->id, 'name' => 'kabel UTI']))
        ->toThrow(RuntimeException::class);
});

test('stock movement records the ledger trail', function () {
    $stock = MaterialStock::factory()->create(['balance' => 10]);
    $movement = StockMovement::factory()->create([
        'material_stock_id' => $stock->id,
        'type' => StockMovement::USAGE,
        'quantity' => 4,
        'before_balance' => 10,
        'after_balance' => 6,
    ]);

    expect($movement->materialStock->is($stock))->toBeTrue()
        ->and($movement->before_balance)->toBe(10)
        ->and($movement->after_balance)->toBe(6)
        ->and(StockMovement::TYPES)->toContain('RECEIPT', 'USAGE', 'ADJUSTMENT', 'DAMAGED', 'LOST', 'RETURN');
});

test('audit log is append-only by shape and stores both time frames', function () {
    $entry = AuditLog::factory()->create();

    expect(Schema::hasColumn('audit_logs', 'updated_at'))->toBeFalse()
        ->and($entry->actor_role)->toBe('ADMINISTRATOR')
        ->and($entry->occurred_at)->not->toBeNull()
        ->and($entry->occurred_date_local)->not->toBeNull()
        ->and($entry->before)->toBe(['status' => 'dipakai'])
        ->and($entry->after)->toBe(['status' => 'rusak'])
        ->and($entry->actor)->not->toBeNull();
});

test('deleting an actor nullifies audit attribution but keeps the row', function () {
    $entry = AuditLog::factory()->create();
    $actorId = $entry->actor_id;

    DB::table('users')->where('id', $actorId)->delete();
    $entry->refresh();

    expect($entry->actor_id)->toBeNull()
        ->and($entry->action)->toBe('inventory.status_change');
});
