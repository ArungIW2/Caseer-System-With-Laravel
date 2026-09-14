<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashSessionService
{
    public function open(User $user, Store $store, float $openingCash, ?string $notes = null): CashSession
    {
        if ($openingCash < 0) {
            throw ValidationException::withMessages(['opening_cash' => 'Saldo awal tidak boleh negatif.']);
        }

        return DB::transaction(function () use ($user, $store, $openingCash, $notes): CashSession {
            $existing = CashSession::query()->where('store_id', $store->id)->where('status', 'open')->lockForUpdate()->first();
            if ($existing) {
                throw ValidationException::withMessages(['store_id' => 'Store tersebut masih memiliki sesi kasir yang terbuka.']);
            }

            return CashSession::create([
                'store_id' => $store->id,
                'opened_by' => $user->id,
                'session_number' => $this->number(),
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'expected_cash' => $openingCash,
                'status' => 'open',
                'notes' => $notes,
            ]);
        });
    }

    public function close(User $user, CashSession $session, float $closingCash, ?string $notes = null): CashSession
    {
        if ($closingCash < 0) {
            throw ValidationException::withMessages(['closing_cash' => 'Saldo akhir tidak boleh negatif.']);
        }

        return DB::transaction(function () use ($user, $session, $closingCash, $notes): CashSession {
            /** @var CashSession $locked */
            $locked = CashSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'open') {
                throw ValidationException::withMessages(['session' => 'Sesi kasir sudah ditutup.']);
            }

            $cashSales = $locked->sales()
                ->where('status', 'completed')
                ->whereHas('payments', fn ($query) => $query->where('method', 'cash'))
                ->with(['payments'])
                ->get()
                ->sum(fn ($sale) => (float) $sale->payments->where('method', 'cash')->sum('amount') - (float) $sale->change_total);

            $expected = round((float) $locked->opening_cash + $cashSales, 2);
            $locked->update([
                'closed_by' => $user->id,
                'closed_at' => now(),
                'expected_cash' => $expected,
                'closing_cash' => $closingCash,
                'cash_difference' => round($closingCash - $expected, 2),
                'status' => 'closed',
                'notes' => $notes ?: $locked->notes,
            ]);

            return $locked->fresh(['store', 'opener', 'closer']);
        });
    }

    public function number(): string
    {
        do {
            $number = 'CS-'.now()->format('YmdHis').'-'.str()->upper(str()->random(6));
        } while (CashSession::query()->where('session_number', $number)->exists());

        return $number;
    }
}
