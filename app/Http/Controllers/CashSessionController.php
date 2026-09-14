<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Store;
use App\Services\CashSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashSessionController extends Controller
{
    public function __construct(private readonly CashSessionService $service) {}

    public function index(Request $request): View
    {
        $stores = $this->storesFor($request);
        $sessions = CashSession::query()
            ->with(['store', 'opener', 'closer'])
            ->whereIn('store_id', $stores->pluck('id'))
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString();

        $openSessions = CashSession::query()->with('store')->whereIn('store_id', $stores->pluck('id'))->where('status', 'open')->get();
        return view('cash-sessions.index', compact('sessions', 'stores', 'openSessions'));
    }

    public function create(Request $request): View
    {
        $stores = $this->storesFor($request);
        return view('cash-sessions.form', compact('stores'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $stores = $this->storesFor($request);
        $store = $stores->firstWhere('id', (int) $data['store_id']);
        abort_unless($store, 403);

        $this->service->open($request->user(), $store, (float) $data['opening_cash'], $data['notes'] ?? null);
        return redirect()->route('cash-sessions.index')->with('success', 'Sesi kasir berhasil dibuka.');
    }

    public function closeForm(Request $request, CashSession $cashSession): View
    {
        $this->authorizeSession($request, $cashSession);
        $cashSession->load('store');
        return view('cash-sessions.close', compact('cashSession'));
    }

    public function close(Request $request, CashSession $cashSession): RedirectResponse
    {
        $this->authorizeSession($request, $cashSession);
        $data = $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->close($request->user(), $cashSession, (float) $data['closing_cash'], $data['notes'] ?? null);
        return redirect()->route('cash-sessions.index')->with('success', 'Sesi kasir berhasil ditutup dan direkonsiliasi.');
    }

    public function show(Request $request, CashSession $cashSession): View
    {
        $this->authorizeSession($request, $cashSession);
        $cashSession->load(['store', 'opener', 'closer', 'sales.payments']);
        return view('cash-sessions.show', compact('cashSession'));
    }

    private function storesFor(Request $request)
    {
        $user = $request->user();
        return in_array($user->role, ['super_admin', 'owner'], true)
            ? Store::query()->where('is_active', true)->orderBy('name')->get()
            : Store::query()->whereKey($user->store_id)->where('is_active', true)->get();
    }

    private function authorizeSession(Request $request, CashSession $cashSession): void
    {
        abort_unless($this->storesFor($request)->contains('id', $cashSession->store_id), 403);
    }
}
