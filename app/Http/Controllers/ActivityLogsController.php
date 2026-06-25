<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $order = $request->input('order', 'desc');

        $logs = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->input('start_date'), function ($query) use ($request) {
                return $query->where('created_at', '>=', $request->input('start_date'));
            })
            ->when($request->input('end_date'), function ($query) use ($request) {
                return $query->where('created_at', '<=', $request->input('end_date'));
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('causer', function ($q2) use ($search) {
                            $q2->where('log_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('subject', function ($q3) use ($search) {
                            $q3->where('log_name', 'like', "%{$search}%");
                        });
                });
            });

        // Ordering the logs
        $logs = $logs->orderBy('created_at', $order)
            ->paginate(10)
            ->appends(['search' => $search, 'order' => $order]);

        return view('admin.logs.index', compact('logs'));
    }

    public function exportCsv(Request $request)
    {
        $users = $this->getUsersWithFilters($request);

        $risks = $users->map(function ($user) {
            return $this->calculateRiskForUser($user);
        });

        $filename = 'risk_scores_'.date('Ymd_His').'.csv';

        $response = new StreamedResponse(function () use ($risks) {
            $handle = fopen('php://output', 'w');
            // CSV Header
            fputcsv($handle, [
                'ID',
                'Name',
                'Business Name',
                'CR Number',
                'ID Number',
                'CR/ID Match Score',
                'ID Expiry Score',
                'CR Expiry Score',
                'Business Type Score',
                'Activity Score',
                'CR/ID Total',
                'CR/ID Score',
                'POS Revenue',
                'POS Score',
                'Late Payments',
                'Repayment Score',
                'Industry',
                'Industry Score',
                'City',
                'City Tier Score',
                'Economic Activity Score',
                'Default Rate Score',
                'Location Score',
                'Total Score',
            ]);

            foreach ($risks as $risk) {
                fputcsv($handle, [
                    $risk->id,
                    $risk->name,
                    $risk->business_name,
                    $risk->cr_number,
                    $risk->id_number,
                    $risk->cr_id_match_score,
                    $risk->id_expiry_score,
                    $risk->cr_expiry_score,
                    $risk->business_type_score,
                    $risk->activity_score,
                    $risk->cr_id_total,
                    $risk->cr_id_score,
                    $risk->pos_revenue,
                    $risk->pos_score,
                    $risk->late_payments,
                    $risk->repayment_score,
                    $risk->industry,
                    $risk->industry_score,
                    $risk->location['city'] ?? '-',
                    $risk->location['tier_score'] ?? 0,
                    $risk->location['activity_score'] ?? 0,
                    $risk->location['default_rate_score'] ?? 0,
                    $risk->location_score,
                    $risk->total_score,
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename={$filename}");

        return $response;
    }

    public function exportPdf(Request $request)
    {
        $logs = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->input('start_date'), function ($query) use ($request) {
                return $query->where('created_at', '>=', $request->input('start_date'));
            })
            ->when($request->input('end_date'), function ($query) use ($request) {
                return $query->where('created_at', '<=', $request->input('end_date'));
            })
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('causer', function ($q2) use ($search) {
                            $q2->where('log_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('subject', function ($q3) use ($search) {
                            $q3->where('log_name', 'like', "%{$search}%");
                        });
                });
            });
        // Ordering the logs
        $order = $request->input('order', 'desc');
        $logs = $logs->orderBy('created_at', $order)
            ->get();
        $pdf = Pdf::loadView('admin.logs.pdf', ['logs' => $logs])
            ->setPaper('A4', 'landscape');

        return $pdf->stream('activity_logs_'.date('Ymd_His').'.pdf');
    }
}
