<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Cdr;
use App\Models\SipiTrunk;
use App\Models\NetworkInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isClient()) {
            return redirect()->route('client.dashboard');
        }

        $today = now()->startOfDay();

        $totalCalls = Cdr::where('calldate', '>=', $today)->count();
        $answeredCalls = Cdr::where('calldate', '>=', $today)->where('disposition', 'ANSWERED')->count();
        $busyCalls = Cdr::where('calldate', '>=', $today)->where('disposition', 'BUSY')->count();
        $failedCalls = Cdr::where('calldate', '>=', $today)->where('disposition', 'FAILED')->count();

        $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 2) : 0;
        $totalBillsec = Cdr::where('calldate', '>=', $today)->sum('billsec');
        $acd = $answeredCalls > 0 ? round($totalBillsec / $answeredCalls / 60, 2) : 0;

        // Tráfego por Operadora & DETRAF
        $carriers = Carrier::withCount(['cdrs'])->get();
        $trunks = SipiTrunk::with('carrier')->get();
        $interfaces = NetworkInterface::all();

        return view('dashboard.admin', compact(
            'totalCalls',
            'answeredCalls',
            'busyCalls',
            'failedCalls',
            'asr',
            'acd',
            'totalBillsec',
            'carriers',
            'trunks',
            'interfaces'
        ));
    }

    public function clientIndex()
    {
        $user = Auth::user();
        $clientCode = $user->client_code;

        $query = Cdr::forClient($clientCode)->where('calldate', '>=', now()->startOfDay());

        $totalCalls = (clone $query)->count();
        $answeredCalls = (clone $query)->where('disposition', 'ANSWERED')->count();
        $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 2) : 0;
        $totalBillsec = (clone $query)->sum('billsec');
        $totalCost = (clone $query)->sum('cost');

        $recentCdrs = Cdr::forClient($clientCode)
            ->orderBy('calldate', 'desc')
            ->limit(20)
            ->get();

        return view('dashboard.client', compact(
            'user',
            'totalCalls',
            'answeredCalls',
            'asr',
            'totalBillsec',
            'totalCost',
            'recentCdrs'
        ));
    }
}
