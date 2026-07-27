<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateCsvExport;
use App\Jobs\GeneratePdfReport;
use App\Jobs\SyncGoogleSheets;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    public function pdf(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $filename = 'exports/report_'.Auth::user()->current_team_id.'_'.now()->timestamp.'.pdf';

        $job = new GeneratePdfReport(Auth::id(), Auth::user()->current_team_id, $filters, $filename);
        dispatch($job);

        return response()->json([
            'data' => ['job_id' => $filename],
            'message' => 'PDF report generation started',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function csv(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $filename = 'exports/export_'.Auth::user()->current_team_id.'_'.now()->timestamp.'.csv';

        $job = new GenerateCsvExport(Auth::id(), Auth::user()->current_team_id, $filters, $filename);
        dispatch($job);

        return response()->json([
            'data' => ['job_id' => $filename],
            'message' => 'CSV export generation started',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function ofx(Request $request): \Illuminate\Http\Response
    {
        $filters = $this->extractFilters($request);
        $teamId = Auth::user()->current_team_id;

        $query = Transaction::where('team_id', $teamId)->with('account');

        if (! empty($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['account_ids'])) {
            $query->whereIn('account_id', $filters['account_ids']);
        }

        if (! empty($filters['category_ids'])) {
            $query->whereIn('category_id', $filters['category_ids']);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        $content = $this->buildOfxContent($transactions);

        return response($content, 200, [
            'Content-Type' => 'application/x-ofx',
            'Content-Disposition' => 'attachment; filename="transactions.ofx"',
        ]);
    }

    public function googleSheets(Request $request): JsonResponse
    {
        $request->validate([
            'spreadsheet_id' => ['required', 'string'],
        ]);

        $filters = $this->extractFilters($request);
        $jobId = (string) Str::uuid();

        $job = new SyncGoogleSheets(
            Auth::id(),
            Auth::user()->current_team_id,
            $request->spreadsheet_id,
            $filters,
            $jobId,
        );
        dispatch($job);

        return response()->json([
            'data' => ['job_id' => $jobId],
            'message' => 'Google Sheets sync started',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function download(string $type, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $path = "exports/{$filename}";

        abort_unless(Storage::disk('exports')->exists($path), 404);

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
        ];

        return Storage::disk('exports')->download(
            $path,
            $filename,
            ['Content-Type' => $mimeTypes[$type] ?? 'application/octet-stream'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request): array
    {
        return $request->only(['start_date', 'end_date', 'account_ids', 'category_ids']);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Transaction>  $transactions
     */
    private function buildOfxContent($transactions): string
    {
        $now = now()->format('YmdHis');
        $content = "OFXHEADER:100\nDATA:OFXSGML\nVERSION:102\nSECURITY:NONE\nENCODING:USASCII\nCHARSET:1252\nCOMPRESSION:NONE\nOLDFILEUID:NONE\nNEWFILEUID:NONE\n\n";
        $content .= "<OFX>\n<SIGNONMSGSRSV1>\n<SONRS>\n<STATUS>\n<CODE>0\n<SEVERITY>INFO\n</STATUS>\n<DTSERVER>{$now}\n<LANGUAGE>ENG\n</SONRS>\n</SIGNONMSGSRSV1>\n";
        $content .= "<BANKMSGSRSV1>\n<STMTTRNRS>\n<TRNUID>1\n<STATUS>\n<CODE>0\n<SEVERITY>INFO\n</STATUS>\n<STMTRS>\n<BANKACCTFROM>\n<ACCTID>0001\n</BANKACCTFROM>\n<BANKTRANLIST>\n";

        foreach ($transactions as $transaction) {
            $date = $transaction->transaction_date->format('Ymd');
            $amount = number_format((float) $transaction->amount, 2, '.', '');
            $type = in_array($transaction->type->value ?? $transaction->type, ['income']) ? 'CREDIT' : 'DEBIT';
            $name = htmlspecialchars($transaction->description ?? 'Transaction', ENT_XML1);
            $content .= "<STMTTRN>\n<TRNTYPE>{$type}\n<DTPOSTED>{$date}\n<TRNAMT>{$amount}\n<NAME>{$name}\n</STMTTRN>\n";
        }

        $content .= "</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>\n</BANKMSGSRSV1>\n</OFX>";

        return $content;
    }
}
