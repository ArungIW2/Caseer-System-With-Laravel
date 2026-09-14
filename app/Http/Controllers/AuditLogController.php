<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Store;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with(['user', 'store'])
            ->when($request->filled('event'), fn ($q) => $q->where('event', 'ilike', '%' . $request->string('event') . '%'))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->latest('created_at')->paginate(25)->withQueryString();
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        return view('audit-logs.index', compact('logs', 'stores'));
    }
}
