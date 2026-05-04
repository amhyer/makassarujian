<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Common\Entity\Row;
use Barryvdh\DomPDF\Facade\Pdf;

class ExamReportController extends Controller
{
    /**
     * Export exam results to Excel.
     */
    public function exportExcel(string $examId)
    {
        $tenantId = Auth::user()->tenant_id;
        $exam = Exam::where('id', $examId)->where('tenant_id', $tenantId)->firstOrFail();
        
        $attempts = Attempt::with('user')
            ->where('exam_id', $examId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->get();

        $fileName = "Hasil_Ujian_{$exam->title}_" . now()->format('YmdHis') . ".xlsx";
        $filePath = storage_path("app/public/reports/{$fileName}");

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($filePath);

        // Header
        $headerRow = WriterEntityFactory::createRowFromArray([
            'No', 'Nama Siswa', 'Email', 'Nilai', 'Mulai', 'Selesai', 'Durasi (Menit)'
        ]);
        $writer->addRow($headerRow);

        // Data
        foreach ($attempts as $index => $attempt) {
            $duration = $attempt->completed_at ? $attempt->started_at->diffInMinutes($attempt->completed_at) : '-';
            $row = WriterEntityFactory::createRowFromArray([
                $index + 1,
                $attempt->user->name,
                $attempt->user->email,
                $attempt->score ?? 0,
                $attempt->started_at->format('Y-m-d H:i:s'),
                $attempt->completed_at ? $attempt->completed_at->format('Y-m-d H:i:s') : '-',
                $duration
            ]);
            $writer->addRow($row);
        }

        $writer->close();

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /**
     * Export exam results to PDF (Berita Acara / Rekap).
     */
    public function exportPdf(string $examId)
    {
        $tenantId = Auth::user()->tenant_id;
        $exam = Exam::with(['subject', 'gradeLevel'])->where('id', $examId)->where('tenant_id', $tenantId)->firstOrFail();
        
        $attempts = Attempt::with('user')
            ->where('exam_id', $examId)
            ->where('tenant_id', $tenantId)
            ->get();

        $pdf = Pdf::loadView('reports.exam-results', [
            'exam' => $exam,
            'attempts' => $attempts,
            'school' => Auth::user()->tenant,
            'date' => now()->format('d F Y'),
        ]);

        return $pdf->download("Berita_Acara_{$exam->title}.pdf");
    }
}
