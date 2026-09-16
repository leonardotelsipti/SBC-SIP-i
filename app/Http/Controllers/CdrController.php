<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Cdr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CdrController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Cdr::with('carrier');

        if ($user->isClient()) {
            $query->forClient($user->client_code);
        }

        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->carrier_id);
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }
        if ($request->filled('start_date')) {
            $query->where('calldate', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('calldate', '<=', $request->end_date);
        }
        if ($request->filled('src')) {
            $query->where('src', 'like', "%{$request->src}%");
        }
        if ($request->filled('dst')) {
            $query->where('dst', 'like', "%{$request->dst}%");
        }
        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }
        if ($request->filled('trunk')) {
            $query->where('trunk_in', $request->trunk)->orWhere('trunk_out', $request->trunk);
        }

        $cdrs = $query->orderBy('calldate', 'desc')->paginate(50)->withQueryString();
        $carriers = Carrier::all();

        return view('cdrs.index', compact('cdrs', 'carriers'));
    }
}
