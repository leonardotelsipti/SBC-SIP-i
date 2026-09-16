<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Cdr;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DetrafReportController extends Controller
{
    /**
     * Relatório Consolidado de DETRAF (Entrada & Saída por Concessionária STFC)
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $carrierFilter = $request->input('carrier_id');

        $carriers = Carrier::all();
        $summaries = [];

        foreach ($carriers as $carrier) {
            if ($carrierFilter && $carrierFilter !== 'all' && (string)$carrier->id !== (string)$carrierFilter) {
                continue;
            }
            $summaries[] = $carrier->getDetrafSummary($startDate, $endDate);
        }

        // Totais gerais
        $totalInboundReceivable = array_sum(array_column($summaries, 'inbound_receivable'));
        $totalOutboundPayable = array_sum(array_column($summaries, 'outbound_payable'));
        $totalNetBalance = $totalInboundReceivable - $totalOutboundPayable;
        $totalInboundMinutes = array_sum(array_column($summaries, 'inbound_minutes'));
        $totalOutboundMinutes = array_sum(array_column($summaries, 'outbound_minutes'));

        return view('admin.detraf.index', compact(
            'carriers',
            'summaries',
            'startDate',
            'endDate',
            'carrierFilter',
            'totalInboundReceivable',
            'totalOutboundPayable',
            'totalNetBalance',
            'totalInboundMinutes',
            'totalOutboundMinutes'
        ));
    }

    /**
     * Exportação de Planilha DETRAF para conciliação contábil com a operadora
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $carrierId = $request->input('carrier_id');
        $direction = $request->input('direction', 'ALL');

        $query = Cdr::with('carrier')->orderBy('calldate', 'desc');

        if ($carrierId && $carrierId !== 'all') {
            $query->where('carrier_id', $carrierId);
        }
        if ($direction !== 'ALL') {
            $query->where('direction', $direction);
        }

        $records = $query->limit(10000)->get();

        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=DETRAF_Telsipti_' . date('Ymd_His') . '.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($records) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM UTF-8

            fputcsv($file, [
                'ID Chamada',
                'Data/Hora',
                'Direcao',
                'Operadora',
                'RN1',
                'Origem (A)',
                'Destino (B)',
                'Duracao (s)',
                'Segundos Faturados',
                'Minutos Faturados',
                'Disposicao',
                'Tarifa DETRAF (R$/min)',
                'Tipo DETRAF',
                'Valor Apurado (R$)',
                'Tronco Entrada',
                'Tronco Saida',
                'Causa ISUP',
            ], ';');

            foreach ($records as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->calldate->format('Y-m-d H:i:s'),
                    $row->direction,
                    $row->carrier->name ?? $row->carrier_name ?? 'N/A',
                    $row->carrier->rn1_code ?? '031',
                    $row->src,
                    $row->dst,
                    $row->duration,
                    $row->billsec,
                    round($row->billsec / 60, 2),
                    $row->disposition,
                    number_format($row->detraf_rate, 4, ',', '.'),
                    $row->detraf_type,
                    number_format($row->detraf_amount, 4, ',', '.'),
                    $row->trunk_in,
                    $row->trunk_out,
                    $row->isup_cause . ' - ' . $row->isup_cause_desc,
                ], ';');
            }

            fclose($file);
        }, 200, $headers);
    }
}
