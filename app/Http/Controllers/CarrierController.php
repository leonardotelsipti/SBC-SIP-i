<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use Illuminate\Http\Request;

class CarrierController extends Controller
{
    public function index()
    {
        $carriers = Carrier::withCount(['trunks', 'cdrs'])->get();
        return view('admin.carriers.index', compact('carriers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rn1_code' => 'required|string|size:3|unique:carriers,rn1_code',
            'inbound_rate' => 'required|numeric|min:0',
            'outbound_rate' => 'required|numeric|min:0',
            'carrier_type' => 'required|in:INCUMBENT_CONCESSIONARIA,AUTORIZADA_ESPELHO,MOVEL_SMP,TRANSITO_ATACADO',
            'contact_noc' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Carrier::create($validated);

        return redirect()->route('admin.carriers.index')
            ->with('success', "Operadora {$validated['name']} cadastrada com sucesso!");
    }

    public function update(Request $request, Carrier $carrier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rn1_code' => 'required|string|size:3|unique:carriers,rn1_code,' . $carrier->id,
            'inbound_rate' => 'required|numeric|min:0',
            'outbound_rate' => 'required|numeric|min:0',
            'carrier_type' => 'required|in:INCUMBENT_CONCESSIONARIA,AUTORIZADA_ESPELHO,MOVEL_SMP,TRANSITO_ATACADO',
            'contact_noc' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $carrier->update($validated);

        return redirect()->route('admin.carriers.index')
            ->with('success', "Tarifas da operadora {$carrier->name} atualizadas com sucesso!");
    }

    public function destroy(Carrier $carrier)
    {
        if ($carrier->trunks()->exists()) {
            return back()->with('error', "Não é possível excluir a operadora pois existem troncos SIP-i vinculados a ela.");
        }

        $carrier->delete();
        return redirect()->route('admin.carriers.index')->with('success', 'Operadora removida com sucesso!');
    }
}
