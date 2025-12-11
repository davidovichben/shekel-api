<?php

namespace App\Http\Controllers;

use App\Models\MemberCreditCard;
use App\Models\Receipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;

class BillingController extends Controller
{
    /**
     * Store a credit card token (mock implementation).
     * In production, this will call Tranzila's store token API.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'last_digits' => 'required|string|size:4',
            'company' => 'required|string|in:visa,mastercard,amex,discover,jcb,diners,unknown',
            'expiration' => 'required|string|max:5',
            'full_name' => 'required|string|max:255',
        ]);

        // Mock: Generate a random token (in production, this comes from Tranzila)
        $token = 'TRZ_' . Str::random(32);

        // Store the credit card
        $creditCard = MemberCreditCard::create([
            'member_id' => $validated['member_id'],
            'token' => $token,
            'last_digits' => $validated['last_digits'],
            'company' => $validated['company'],
            'expiration' => $validated['expiration'],
            'full_name' => $validated['full_name'],
            'is_default' => MemberCreditCard::where('member_id', $validated['member_id'])->count() === 0,
        ]);

        return response()->json([
            'success' => true,
            'credit_card' => [
                'id' => $creditCard->id,
                'last_digits' => $creditCard->last_digits,
                'company' => $creditCard->company,
                'expiration' => $creditCard->expiration,
                'full_name' => $creditCard->full_name,
                'is_default' => $creditCard->is_default,
            ],
        ], 201);
    }

    /**
     * Charge a credit card (mock implementation).
     * In production, this will call Tranzila's charge API.
     */
    public function charge(Request $request): JsonResponse
    {
        // Handle both multipart/form-data and application/json
        $isMultipart = $request->hasFile('receipt_pdf');
        
        if ($isMultipart) {
            // Multipart form data (with PDF file)
            $validated = $request->validate([
                'credit_card_id' => 'required|exists:member_credit_cards,id',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:500',
                'type' => 'nullable|in:vows,community_donations,external_donations,ascensions,online_donations,membership_fees,other',
                'receipt_pdf' => 'required|file|mimes:pdf|max:51200', // 50MB max
            ]);
        } else {
            // JSON request (backward compatible)
            $validated = $request->validate([
                'credit_card_id' => 'required|exists:member_credit_cards,id',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:500',
                'type' => 'nullable|in:vows,community_donations,external_donations,ascensions,online_donations,membership_fees,other',
                'createReceipt' => 'nullable|boolean',
            ]);
        }

        $creditCard = MemberCreditCard::findOrFail($validated['credit_card_id']);

        // Mock: Simulate processing delay
        usleep(500000); // 0.5 second delay

        // Mock: Always return success (in production, this depends on Tranzila response)
        $transactionId = 'TXN_' . Str::random(16);

        // Get authenticated user
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }
        
        $amount = (float) $validated['amount'];

        // Create receipt only if createReceipt is true OR if PDF is uploaded
        $shouldCreateReceipt = $isMultipart || (!empty($validated['createReceipt']) && $validated['createReceipt']);
        $receipt = null;
        $pdfPath = null;

        if ($shouldCreateReceipt) {
            // Create receipt without receipt_number and receipt_date initially
            // These will be set when the receipt is actually generated (PDF created/uploaded)
            $receipt = Receipt::create([
                'user_id' => $user->id,
                'total' => $amount,
                'status' => 'paid',
                'payment_method' => 'credit_card',
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'] ?? 'other',
            ]);

            // Handle PDF file upload (from client) or server-side generation (backward compatibility)
            if ($isMultipart && $request->hasFile('receipt_pdf')) {
                // Client uploaded PDF file - generate receipt number and date
                try {
                    $file = $request->file('receipt_pdf');
                    $filename = 'receipt_' . $receipt->id . '_' . time() . '.pdf';
                    $path = $file->storeAs('receipts', $filename, 'public');
                    $pdfPath = $path;
                    
                    // Set receipt_number and receipt_date when receipt is generated
                    $receipt->update([
                        'pdf_file' => $pdfPath,
                        'receipt_number' => $transactionId,
                        'receipt_date' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('PDF Upload Error: ' . $e->getMessage(), [
                        'receipt_id' => $receipt->id,
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Continue even if PDF upload fails
                }
            } elseif (!empty($validated['createReceipt']) && $validated['createReceipt']) {
                // Server-side PDF generation (backward compatibility)
                try {
                    // Set receipt_number and receipt_date before generating PDF
                    $receipt->update([
                        'receipt_number' => $transactionId,
                        'receipt_date' => now(),
                    ]);
                    
                    $pdfPath = $this->generateReceiptPdf($receipt);
                    $receipt->update(['pdf_file' => $pdfPath]);
                } catch (\Throwable $e) {
                    Log::error('PDF Generation Error: ' . $e->getMessage(), [
                        'receipt_id' => $receipt->id,
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Continue even if PDF generation fails
                }
            }
        }

        $response = [
            'success' => true,
            'transaction' => [
                'id' => $transactionId,
                'amount' => number_format($amount, 2, '.', ''),
                'credit_card_id' => $creditCard->id,
                'last_digits' => $creditCard->last_digits,
                'description' => $validated['description'] ?? null,
                'status' => 'completed',
            ],
        ];

        if ($receipt) {
            $response['receipt'] = [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'total' => number_format($receipt->total, 2, '.', ''),
                'status' => $receipt->status,
                'type' => $receipt->type,
            ];

            if ($pdfPath) {
                $response['receipt']['pdf_file'] = $pdfPath;
            }
        }

        return response()->json($response);
    }

    /**
     * Generate PDF for a single receipt.
     */
    private function generateReceiptPdf(Receipt $receipt): string
    {
        $receipt->load('user');
        
        $typeLabels = [
            'vows' => 'נדרים',
            'community_donations' => 'תרומות מהקהילה',
            'external_donations' => 'תרומות חיצוניות',
            'ascensions' => 'עליות',
            'online_donations' => 'תרומות אונליין',
            'membership_fees' => 'דמי חברים',
            'other' => 'אחר',
        ];

        $statusLabels = [
            'pending' => 'ממתין',
            'paid' => 'שולם',
            'cancelled' => 'בוטל',
            'refunded' => 'הוחזר',
        ];

        $receiptDate = $receipt->receipt_date ? \Carbon\Carbon::parse($receipt->receipt_date)->format('d/m/Y') : '';
        $typeLabel = $typeLabels[$receipt->type] ?? $receipt->type;
        $statusLabel = $statusLabels[$receipt->status] ?? $receipt->status;

        $html = '
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
                        <td>' . htmlspecialchars($receipt->receipt_number) . '</td>
                    </tr>
                    <tr>
                        <td>תאריך:</td>
                        <td>' . htmlspecialchars($receiptDate) . '</td>
                    </tr>
                    <tr>
                        <td>משתמש:</td>
                        <td>' . htmlspecialchars($receipt->user ? $receipt->user->name : '') . '</td>
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
            
            // Generate filename
            $filename = 'receipt_' . $receipt->id . '_' . time() . '.pdf';
            $path = 'receipts/' . $filename;
            
            // Save to storage
            $pdfContent = $mpdf->Output('', 'S');
            Storage::disk('public')->put($path, $pdfContent);
            
            return $path;
        } catch (\Throwable $e) {
            Log::error('mPDF Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
