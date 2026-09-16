<?php

namespace App\Http\Controllers;

use App\Models\NetworkAlias;
use App\Services\LinuxNetworkService;
use Illuminate\Http\Request;

class NetworkAliasController extends Controller
{
    public function index()
    {
        $aliases = NetworkAlias::all();
        return view('network.aliases', compact('aliases'));
    }

    public function store(Request $request, LinuxNetworkService $networkService)
    {
        $validated = $request->validate([
            'alias_id' => 'required|string|max:10',
            'device' => 'required|string|max:30',
            'ip_address' => 'required|ip',
            'netmask' => 'required|string|max:45',
        ]);

        $alias = NetworkAlias::create($validated);

        // Replica imediatamente no Linux via sudo /sbin/ip
        $cmdResult = $networkService->addIpAlias(
            $alias->device,
            $alias->ip_address,
            $alias->netmask,
            $alias->alias_id
        );

        $msg = "IP Virtual {$alias->device}:{$alias->alias_id} salvo no banco!";
        if (!$cmdResult['success']) {
            $msg .= " Aviso: O kernel retornou erro ao aplicar: " . $cmdResult['output'];
        }

        return redirect()->back()->with('success', $msg);
    }

    public function destroy(NetworkAlias $alias, LinuxNetworkService $networkService)
    {
        // Remove do Linux
        $networkService->deleteIpAlias($alias->device, $alias->ip_address, $alias->netmask);

        // Remove do Banco
        $alias->delete();

        return redirect()->back()->with('success', "IP Virtual {$alias->device}:{$alias->alias_id} removido do banco e do Linux!");
    }
}
