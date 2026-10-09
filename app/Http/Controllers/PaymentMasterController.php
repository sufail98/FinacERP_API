<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\PaymentMaster;
use App\Models\PaymentDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class PaymentMasterController extends Controller
{
    /**
     * List Payment Voucher
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $payment = DB::select('SELECT * FROM public.get_payment_voucher_master_list(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($payment);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Payment Voucher Retrieved Successfully',
            'data' => $paginated,
            'meta'    => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => ceil($total / $limit),
            ]
        ]);
    }

    /**
     * Get Payment Voucher by id
     */
    public function show($id)
    {
        $payment = DB::select(
            'SELECT * FROM public.get_payment_master_data_byid(?)',
            [$id]
        );

        if (empty($payment)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Payment Voucher Not Found',
            ], 404);
        }

        $payment = (array) $payment[0];

        // Convert Documents JSON to array of full URLs
        if (!empty($payment['Documents'])) {

            $documents = json_decode($payment['Documents'], true);

            if (is_array($documents)) {

                $payment['Documents'] = array_map(function ($path) {

                    $path = ltrim($path, '/');

                    // If already a full URL
                    if (filter_var($path, FILTER_VALIDATE_URL)) {
                        return $path;
                    }

                    return asset('storage/' . $path);
                }, $documents);
            } else {
                $payment['Documents'] = [];
            }
        } else {
            $payment['Documents'] = [];
        }

        // Get payment details
        $paymentDetails = DB::select(
            'SELECT * FROM public.get_payment_details_by_masterid(?)',
            [$payment['paymentMasterId']]
        );

        $payment['paymentDetails'] = array_map(function ($item) {
            return (array) $item;
        }, $paymentDetails);

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Payment Voucher Retrieved Successfully',
            'data'    => $payment,
        ], 200);
    }

    /**
     * Create Payment Voucher
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'Documents'   => 'nullable|array',
                'Documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx|max:20480',
            ]);

            // Upload multiple documents
            $uploadedFiles = [];

            if ($request->hasFile('Documents')) {

                foreach ($request->file('Documents') as $file) {

                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                    $file->move(public_path('storage/paymentmaster_documents'), $fileName);

                    $uploadedFiles[] = 'paymentmaster_documents/' . $fileName;
                }
            }

            $documents = !empty($uploadedFiles) ? json_encode($uploadedFiles) : null;

            $branchId = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $createdDate = getBranchNow($branchId);

            $result = DB::select(
                'SELECT public.insert_payment_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS "out_paymentMasterId"',
                [
                    $codeData['table_pk'],                                   // paymentMasterId
                    $codeData['voucherNo'],                                 // voucherNo
                    $codeData['suffixPrefixId'],                            // suffixPrefixId
                    $codeData['voucherCode'],                               // paymentNo
                    $request->date,
                    $request->ledgerId,
                    $request->narration,
                    $request->totalAmount,
                    $request->costCentreId,
                    $request->ReferenceNo,
                    $request->ReferenceDate,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $branchId,
                    $createdDate,
                    $request->CreatedUser,
                    $documents,

                ]
            );


            $payment = PaymentMaster::find($result[0]->out_paymentMasterId);

            if ($payment->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payment->date,           // date
                    'Payment Voucher',         // voucherType
                    $payment->paymentMasterId,  // voucherNo
                    $payment->ledgerId,          // ledgerId
                    0,    // debit
                    $payment->totalAmount,                        // credit
                    $payment->paymentNo,         // InvoiceNo
                    $payment->narration,         // Narration
                    $payment->costCentreId,      // costCentreId
                    'Payment',                   // PostingType
                    $request->exchangeRate,      // exchangeRate
                    $request->exchangeDate,      // exchangeDate
                    $payment->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // Insert into tbl_PaymentDetails
            if ($request->has('paymentDetails')) {
                foreach ($request->paymentDetails as $sd) {
                    $result = DB::select(
                        'SELECT public.insert_payment_details(
							?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
						) AS "out_paymentDetailsId"',
                        [
                            (PaymentDetails::max('paymentDetailsId') ?? 0) + 1,  // paymentDetailsId (or generate one before)
                            $payment->paymentMasterId,        // paymentMasterId
                            $sd['ledgerId'],                  // ledgerId
                            $sd['amount'],                    // amount
                            $sd['currencyConversionId'],      // currencyConversionId
                            $request->exchangeRate,           // exchangeRate
                            $request->exchangeDate,           // exchangeDate
                            $sd['chequeNo'],                  // chequeNo
                            $sd['chequeDate'],                // chequeDate
                            $sd['lineIndex'],                 // lineIndex
                            $sd['narration'],                 // Narration
                            $request->branchId,               // branchId
                            $createdDate, // CreatedDate
                            $request->CreatedUser,               // CreatedUser
                            NULL,             // ModifiedDate
                            NULL              // ModifiedUser
                        ]
                    );

                    $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $payment->date,               // date
                        'Payment Voucher',             // voucherType
                        $payment->paymentMasterId,          // voucherNo
                        $sd['ledgerId'],              // ledgerId
                        $sd['amount'],                // debit
                        0,                            // credit
                        $payment->paymentNo,         // InvoiceNo
                        $sd['narration'],             // Narration
                        $payment->costCentreId,      // costCentreId
                        'Payment',                   // PostingType
                        $request->exchangeRate,      // exchangeRate
                        $request->exchangeDate,      // exchangeDate
                        $request->branchId,          // branchId
                        $request->CreatedUser,       // CreatedUser
                        $createdDate
                    ]);
                }
            }

            // if ($request->has('partyDetails')) {
            //     foreach ($request->partyDetails as $pd) {
            //         // Partybalance Posting
            //         $result = DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            //             $pd['date'],                     // date
            //             $payment->ledgerId,                 // ledgerId
            //             'Payment Voucher',                  // voucherType
            //             $payment->paymentMasterId,                 // voucherNo
            //             $pd['againstVoucherType'],       // againstVoucherType
            //             $pd['againstvoucherNo'],           // againstvoucherNo
            //             $pd['referenceType'],           // referenceType
            //             $pd['Amount'],                     // debit
            //             0,                              // credit
            //             $pd['creditPeriod'],             // creditPeriod
            //             $pd['currencyConversionId'],   // currencyConversionId
            //             $payment->paymentNo,                 // invoiceNo
            //             $pd['referenceNo'],                  // referenceNo
            //             $pd['BillAmount'],                     // BillAmount
            //             $pd['date'],                        // invoiceDate
            //             $payment->costCentreId,              // costCentreId
            //             $request->exchangeRate,              // exchangeRate
            //             $request->exchangeDate,              // exchangeDate
            //             $payment->branchId,                  // branchId
            //             $request->CreatedUser              // CreatedUser
            //         ]);
            //     }
            // }
            if ($request->has('partyDetails')) {
                foreach ($request->partyDetails as $pd) {

                    if ($pd['referenceType'] === 'Against') {

                        // Existing bill/payment reference
                        $partyVoucherType        = $pd['voucherType'];     // referenced voucher type
                        $partyVoucherNo          = $pd['masterId'];        // referenced masterId
                        $partyAgainstVoucherType = 'Payment Voucher';
                        $partyAgainstvoucherNo   = $payment->paymentMasterId;
                    } else {

                        // New / OnAccount
                        $partyVoucherType        = 'Payment Voucher';
                        $partyVoucherNo          = $payment->paymentMasterId;
                        $partyAgainstVoucherType = 'NA';
                        $partyAgainstvoucherNo   = 'NA';
                    }

                    DB::select(
                        'SELECT * FROM insert_party_balance(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?
            )',
                        [
                            $payment->date,                 // date
                            $pd['ledgerId'],               // ledgerId

                            $partyVoucherType,             // voucherType
                            $partyVoucherNo,               // voucherNo

                            $partyAgainstVoucherType,      // againstVoucherType
                            $partyAgainstvoucherNo,        // againstvoucherNo

                            $pd['referenceType'],          // referenceType

                            $pd['amount'],                 // debit
                            0,                             // credit

                            $pd['creditPeriod'],           // creditPeriod
                            $pd['currencyConversionId'],   // currencyConversionId

                            $pd['invoiceNo'],              // invoiceNo
                            $pd['referenceNo'],            // referenceNo
                            $pd['BillAmount'],             // BillAmount
                            $pd['invoiceDate'],            // invoiceDate

                            $payment->costCentreId,        // costCentreId
                            $request->exchangeRate,        // exchangeRate
                            $request->exchangeDate,        // exchangeDate
                            $payment->branchId,            // branchId
                            $request->CreatedUser,          // CreatedUser
                            $createdDate
                        ]
                    );
                }
            }
            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Payment Voucher Inserted Successfully'
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Payment Voucher
     */
    public function update(Request $request, $id)
    {
        $payment = PaymentMaster::find($id);

        if (!$payment) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Payment Voucher Not Found',
            ], 404);
        }

        $oldValue = $payment->totalAmount;

        DB::beginTransaction();

        try {

            // Keep existing documents by default
            $documents = $payment->Documents;

            if ($request->hasFile('Documents')) {

                $request->validate([
                    'Documents'   => 'required|array',
                    'Documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx|max:20480'
                ]);

                // Delete old files
                $oldFiles = json_decode($payment->Documents, true);

                if (is_array($oldFiles)) {
                    foreach ($oldFiles as $oldFile) {
                        if (!empty($oldFile) && file_exists(public_path($oldFile))) {
                            unlink(public_path($oldFile));
                        }
                    }
                }

                $destinationPath = public_path('storage/paymentmaster_documents');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $uploadedFiles = [];

                foreach ($request->file('Documents') as $file) {

                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

                    $file->move($destinationPath, $fileName);

                    // $uploadedFiles[] = 'storage/paymentmaster_documents/' . $fileName;
                    $uploadedFiles[] = 'paymentmaster_documents/' . $fileName;
                }

                $documents = json_encode($uploadedFiles);
            }
            $branchId   = $request->branchId;
            $modifiedDate = getBranchNow($branchId);
            $oldValue    = $payment->totalAmount;
$oldBranchId = $payment->branchId;
            // Update Payment Master
            $result = DB::select(
                'SELECT * FROM public.update_payment_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )',
                [
                    $id,
                    $request->date,
                    $request->ledgerId,
                    $request->narration,
                    $request->totalAmount,
                    $request->costCentreId,
                    $request->ReferenceNo,
                    $request->ReferenceDate,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $request->branchId,
                    $modifiedDate,
                    $request->ModifiedUser,
                    $documents
                ]
            );

            $payment->refresh();

            if ($payment->totalAmount > 0) {
                DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                    'Payment Voucher', // voucherType
                    $payment->paymentMasterId, // voucherNo
                   $oldBranchId   // branchId
                ]);

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payment->date,           // date
                    'Payment Voucher',         // voucherType
                    $payment->paymentMasterId,  // voucherNo
                    $payment->ledgerId,          // ledgerId
                    0,    // debit
                    $payment->totalAmount,                        // credit
                    $payment->paymentNo,         // InvoiceNo
                    $payment->narration,         // Narration
                    $payment->costCentreId,      // costCentreId
                    'Payment',                   // PostingType
                    $request->exchangeRate,      // exchangeRate
                    $request->exchangeDate,      // exchangeDate
                    $payment->branchId,          // branchId
                    $payment->CreatedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($request->has('paymentDetails')) {
                DB::select('SELECT delete_payment_details_byid(?)', [$payment->paymentMasterId]);

                foreach ($request->paymentDetails as $sd) {
                    $result = DB::select(
                        'SELECT public.insert_payment_details(
							?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
						) AS "out_paymentDetailsId"',
                        [
                            (PaymentDetails::max('paymentDetailsId') ?? 0) + 1,  // paymentDetailsId (or generate one before)
                            $payment->paymentMasterId,        // paymentMasterId
                            $sd['ledgerId'],                  // ledgerId
                            $sd['amount'],                    // amount
                            $sd['currencyConversionId'],      // currencyConversionId
                            $request->exchangeRate,           // exchangeRate
                            $request->exchangeDate,           // exchangeDate
                            $sd['chequeNo'],                  // chequeNo
                            $sd['chequeDate'],                // chequeDate
                            $sd['lineIndex'],                 // lineIndex
                            $sd['narration'],                 // Narration
                            $request->branchId,               // branchId
                            NULL,                               // CreatedDate
                            NULL,                             // CreatedUser
                            $modifiedDate,             // ModifiedDate
                            $request->ModifiedUser              // ModifiedUser
                        ]
                    );

                    $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $payment->date,               // date
                        'Payment Voucher',             // voucherType
                        $payment->paymentMasterId,          // voucherNo
                        $sd['ledgerId'],              // ledgerId
                        $sd['amount'],                // debit
                        0,                            // credit
                        $payment->paymentNo,         // InvoiceNo
                        $sd['narration'],             // Narration
                        $payment->costCentreId,      // costCentreId
                        'Payment',                   // PostingType
                        $request->exchangeRate,      // exchangeRate
                        $request->exchangeDate,      // exchangeDate
                        $request->branchId,          // branchId
                        $payment->CreatedUser,       // CreatedUser
                        $modifiedDate
                    ]);
                }
            }

            if ($request->has('partyDetails')) {

                DB::select('SELECT delete_partybalance(?, ?)', [
                    'Payment Voucher',
                    $payment->paymentMasterId
                ]);

                foreach ($request->partyDetails as $pd) {

                    if ($pd['referenceType'] === 'Against') {

                        // Existing bill reference
                        $partyVoucherType        = $pd['voucherType'];
                        $partyVoucherNo          = $pd['masterId'];

                        $partyAgainstVoucherType = 'Payment Voucher';
                        $partyAgainstvoucherNo   = $payment->paymentMasterId;
                    } else {

                        // New / OnAccount
                        $partyVoucherType        = 'Payment Voucher';
                        $partyVoucherNo          = $payment->paymentMasterId;

                        $partyAgainstVoucherType = 'NA';
                        $partyAgainstvoucherNo   = 'NA';
                    }

                    DB::select(
                        'SELECT * FROM insert_party_balance(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?
            )',
                        [
                            $payment->date,                 // date
                            $pd['ledgerId'],               // ledgerId

                            $partyVoucherType,             // voucherType
                            $partyVoucherNo,               // voucherNo

                            $partyAgainstVoucherType,      // againstVoucherType
                            $partyAgainstvoucherNo,        // againstvoucherNo

                            $pd['referenceType'],          // referenceType

                            $pd['amount'],                 // debit
                            0,                             // credit

                            $pd['creditPeriod'],           // creditPeriod
                            $pd['currencyConversionId'],   // currencyConversionId

                            $pd['invoiceNo'],              // invoiceNo
                            $pd['referenceNo'],            // referenceNo
                            $pd['BillAmount'],             // BillAmount
                            $pd['invoiceDate'],            // invoiceDate

                            $payment->costCentreId,        // costCentreId
                            $request->exchangeRate,        // exchangeRate
                            $request->exchangeDate,        // exchangeDate
                            $payment->branchId,            // branchId
                            $request->ModifiedUser,         // ModifiedUser
                            $modifiedDate
                        ]
                    );
                }
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Payment Voucher Update',
                'Payment Voucher',
                'UPDATED',
                $payment->branchId,
                $payment->ModifiedUser ?? 'System',
                $payment->paymentMasterId,
                $payment->voucherNo,
                $payment->date,
                $oldValue,
                $request->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Payment Voucher Updated Successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete Payment Voucher
     */
    public function destroy($id)
    {
        $payment = PaymentMaster::find($id);

        if (!$payment) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Payment Voucher Not Found',
            ], 404);
        }
        $oldValue = $payment->totalAmount;
        DB::beginTransaction();
        try {
            DB::select('SELECT delete_payment_details_byid(?)', [$id]);
            DB::statement('SELECT public.delete_payment_master_byid(?)', [$id]);

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Payment Voucher', // voucherType
                $payment->paymentMasterId, // voucherNo
                null   // branchId
            ]);

            DB::select('SELECT delete_partybalance(?, ?)', [
                'Payment Voucher', // voucherType
                $payment->paymentMasterId // voucherNo
            ]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Payment Voucher Delete',
                'Payment Voucher',
                'DELETED',
                $payment->branchId,
                $payment->ModifiedUser ?? 'System',
                $payment->paymentMasterId,
                $payment->voucherNo,
                $payment->date,
                $oldValue,
                0
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Payment Voucher Deleted Successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentReport(Request $request)
    {
        try {
            $fromDate      = $request->from_date;
            $toDate        = $request->to_date;
            $postedStatus  = $request->posted_status ?? null;
            $ledgerId      = $request->ledger_id ?? null;
            $branchId      = $request->branch_id ?? null;
            $currencyId    = $request->currency_id ?? null;
            $detailed      = $request->detailed;
            $ledgerName    = $request->ledger_name ?? '';
            $userId        = $request->user_id ?? null;
            $costCentreId  = $request->cost_centre_id ?? null;

            $data = DB::select(
                'SELECT * FROM "payment_report_fill"(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $fromDate,
                    $toDate,
                    $postedStatus,
                    $ledgerId,
                    $branchId,
                    $currencyId,
                    $ledgerName,
                    $userId,
                    $costCentreId,
                    $request->mode
                ]
            );

            return response()->json([
                'status' => 200,
                'errors' => false,
                'data' => $data,
                'message' => 'Payment Report Data Fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}