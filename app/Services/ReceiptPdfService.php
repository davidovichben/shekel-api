<?php

namespace App\Services;

use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

class ReceiptPdfService
{
    protected array $typeLabels = [
        'general' => 'כללי',
        'vows' => 'נדרים',
        'community_donations' => 'תרומות מהקהילה',
        'external_donations' => 'תרומות חיצוניות',
        'ascensions' => 'עליות',
        'online_donations' => 'תרומות אונליין',
        'membership_fees' => 'דמי חברים',
        'other' => 'אחר',
    ];

    protected array $statusLabels = [
        'pending' => 'ממתין',
        'paid' => 'שולם',
        'failed' => 'נכשל',
        'cancelled' => 'בוטל',
        'refunded' => 'הוחזר',
    ];

    /**
     * Generate PDF for a receipt and save it to storage.
     *
     * @return string The storage path of the generated PDF
     */
    public function generate(Receipt $receipt): string
    {
        $receipt->load('member');

        $html = $this->buildHtml($receipt);

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'default_font' => 'dejavusans',
            ]);

            $mpdf->SetDirectionality('rtl');
            $mpdf->WriteHTML($html);

            $filename = 'receipt_' . $receipt->id . '_' . time() . '.pdf';
            $path = 'receipts/' . $filename;

            $pdfContent = $mpdf->Output('', 'S');
            Storage::disk('public')->put($path, $pdfContent);

            return $path;
        } catch (\Throwable $e) {
            Log::error('Receipt PDF generation error: ' . $e->getMessage(), [
                'receipt_id' => $receipt->id,
            ]);
            throw $e;
        }
    }

    /**
     * Build the HTML content for the receipt PDF.
     */
    protected function buildHtml(Receipt $receipt): string
    {
        $receiptDate = $receipt->date ? Carbon::parse($receipt->date)->format('d/m/Y') : '';
        $typeLabel = $this->typeLabels[$receipt->type] ?? $receipt->type;
        $statusLabel = $this->statusLabels[$receipt->status] ?? $receipt->status;
        $memberName = $receipt->member ? $receipt->member->full_name : '';

        return '
        <!DOCTYPE html>
        <html dir="rtl" lang="he">
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: DejaVu Sans, Arial, sans-serif;
                    direction: rtl;
                    text-align: right;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .receipt-info {
                    margin-bottom: 20px;
                }
                .receipt-info table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .receipt-info td {
                    padding: 8px;
                    border-bottom: 1px solid #ddd;
                }
                .receipt-info td:first-child {
                    font-weight: bold;
                    width: 30%;
                }
                .amounts {
                    margin-top: 30px;
                    text-align: left;
                }
                .total {
                    font-size: 18px;
                    font-weight: bold;
                    margin-top: 20px;
                    padding-top: 10px;
                    border-top: 2px solid #000;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>קבלה</h1>
                <h2>Receipt</h2>
            </div>

            <div class="receipt-info">
                <table>
                    <tr>
                        <td>מספר קבלה:</td>
                        <td>' . htmlspecialchars($receipt->number ?? '') . '</td>
                    </tr>
                    <tr>
                        <td>תאריך:</td>
                        <td>' . htmlspecialchars($receiptDate) . '</td>
                    </tr>
                    <tr>
                        <td>שם:</td>
                        <td>' . htmlspecialchars($memberName) . '</td>
                    </tr>
                    <tr>
                        <td>סוג:</td>
                        <td>' . htmlspecialchars($typeLabel) . '</td>
                    </tr>
                    <tr>
                        <td>סטטוס:</td>
                        <td>' . htmlspecialchars($statusLabel) . '</td>
                    </tr>
                    <tr>
                        <td>אמצעי תשלום:</td>
                        <td>' . htmlspecialchars($receipt->payment_method ?? '') . '</td>
                    </tr>
                </table>
            </div>

            <div class="amounts">
                <div class="total">
                    סכום כולל: ' . number_format((float)$receipt->total, 2, '.', '') . ' ₪
                </div>
            </div>

            ' . ($receipt->description ? '<div style="margin-top: 30px;"><strong>הערות:</strong><br>' . htmlspecialchars($receipt->description) . '</div>' : '') . '
        </body>
        </html>';
    }
}
