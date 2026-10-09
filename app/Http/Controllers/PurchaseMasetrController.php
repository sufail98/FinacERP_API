<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\PurchaseMaster;
use App\Models\PurchaseDetails1;
use App\Models\AccountLedger;
use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductPurchasePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseMasetrController extends Controller
{
    /**
     * List Purchase Invoice
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $purchase = DB::select('SELECT * FROM public.get_all_purchase_master_list(?,?,?,?,?,?,?,?,?)', [
            $request->fromDate,        // fromdate
            $request->toDate,          // todate
            $limit,                    // limits
            $page,                     // page
            $request->branchId,        // branchid
            $request->vouchercode,     // vouchercode
            $request->ledgerId,        // p_ledgerid
            $request->customername,    // customername
            $request->taxtype,         // taxtype
        ]);

        $collection = collect($purchase);

        $total = $collection->first()->total_count ?? 0;

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Purchase Invoice Retrieved Successfully',
            'data'    => $collection,
            'meta'    => [
                'total'       => (int) $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
            ]
        ]);
    }

    /**
     * Get Purchase Invoice by id
     */
    public function show($id)
    {
        $purchase = DB::select(
            'SELECT * FROM public.get_purchase_master_data_byid(?)',
            [$id]
        );

        if (empty($purchase)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Purchase Invoice Not Found',
            ], 404);
        }

        $purchase = (array) $purchase[0];

        // Convert stored document paths into full URLs
        if (!empty($purchase['Documents'])) {

            $docPaths = json_decode($purchase['Documents'], true);

            if (is_array($docPaths)) {
                $purchase['Documents'] = array_map(function ($path) {

                    // Remove leading slash if present
                    $path = ltrim($path, '/');

                    // If already a full URL, return as is
                    if (filter_var($path, FILTER_VALIDATE_URL)) {
                        return $path;
                    }

                    // Remove storage/ if already stored
                    if (str_starts_with($path, 'storage/')) {
                        $path = substr($path, 8);
                    }

                    return asset('storage/' . $path);
                }, $docPaths);
            } else {
                $purchase['Documents'] = [];
            }
        } else {
            $purchase['Documents'] = [];
        }

        $purchaseDetails = DB::select(
            'SELECT * FROM public.get_purchase_details_by_masterid(?)',
            [$purchase['purchaseMasterId']]
        );

        $purchaseDetails = array_map(function ($item) {

            $item = (array) $item;

            if (!empty($item['productCode'])) {

                $product = Product::with('salesPrices')
                    ->where('productCode', $item['productCode'])
                    ->first();

                if ($product) {
                    $item['salesPrices'] = $product->salesPrices->toArray();
                    $item['category'] = $product->category;
                } else {
                    $item['salesPrices'] = [];
                    $item['category'] = null;
                }
            } else {
                $item['salesPrices'] = [];
                $item['category'] = null;
            }

            return $item;
        }, $purchaseDetails);

        $purchase['purchaseDetails'] = $purchaseDetails;

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Purchase Invoice Retrieved Successfully',
            'data'    => $purchase,
        ], 200);
    }

    /**
     * Create Purchase Invoice
     */
    public function store(Request $request)
    {
        // Validate multiple document uploads
        $request->validate([
            'documents'   => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:5120', // 5MB per file
        ]);
        $vendorInvoiceNo = trim($request->vendorInvoiceNo ?? '');

        if ($vendorInvoiceNo !== '' && !empty($request->ledgerId)) {

            $exists = DB::table('tbl_Purchasemaster')
                ->where('vendorInvoiceNo', $vendorInvoiceNo)
                ->where('ledgerId', $request->ledgerId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Vendor Invoice No already exists for this supplier.'
                ], 409);
            }
        }
        DB::beginTransaction();
        try {
            $branchId    = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId      = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);

            // --- Handle multiple document uploads ---
            $documentPaths = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $storedPath = $file->store('purchase_documents', 'public');
                    $documentPaths[] = $storedPath;
                }
            }
            // Store as JSON so the stored procedure can persist it in a single column
            $documents = !empty($documentPaths) ? json_encode($documentPaths) : null;
            $createdDate = getBranchNow($branchId);

            // Insert Purchase Invoice
            $result = DB::select('SELECT public.insert_purchase_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?, ?
            ) AS out_purchaseMasterId', [
                $codeData['table_pk'],              // purchaseMasterId
                $codeData['suffixPrefixId'],        // suffixPrefixId
                $codeData['voucherNo'],             // voucherNo
                $codeData['voucherCode'],           // invoiceNo
                $request->vendorInvoiceNo,          // vendorInvoiceNo
                $request->date,                     // date
                $request->invoiceDate,              // invoiceDate
                $request->creditPeriod,             // creditPeriod
                $request->ledgerId,                 // ledgerId
                $request->partyName,                // partyName
                $request->partyAddress,             // partyAddress
                $request->partyPhone,               // partyPhone
                $request->RefNo,                    // RefNo
                $request->partyRefDate,             // partyRefDate
                $request->partyVatNo,               // partyVatNo
                $request->narration,                // narration
                $request->purchaseAccount,          // purchaseAccount
                $request->receiptMasterId,          // receiptMasterId
                $request->orderMasterId,            // orderMasterId
                $request->AgainstNo,                // AgainstNo
                $request->costCentreId,             // costCentreId
                $request->BatchId,                  // BatchId
                $request->currencyConversionId,     // currencyConversionId
                $request->exchangeRate,             // exchangeRate
                $request->exchangeDate,             // exchangeDate
                $request->taxType,                  // taxType
                $request->paymentMode,              // paymentMode
                $request->subTotal,                 // subTotal
                $request->additionalCost,           // additionalCost
                $request->billDiscount,             // billDiscount
                $request->othercharge,              // othercharge
                $request->otherChargeLedgerId,      // otherChargeLedgerId
                $request->taxableAmt,               // taxableAmt
                $request->totalTax,                 // totalTax
                $request->roundoff,                 // roundoff
                $request->totalAmount,              // totalAmount
                $request->transportCompany,         // transportCompany
                $request->CashLedgerId,             // CashLedgerId
                $request->CashRefNo,                // CashRefNo
                $request->CashAmount,               // CashAmount
                $request->BankLedgerId,             // BankLedgerId
                $request->BankRefNo,                // BankRefNo
                $request->BankAmount,               // BankAmount
                $request->BillBalanceAmount,        // BillBalanceAmount
                $request->postedStatus,             // postedStatus
                $request->postedBy,                 // postedBy
                $request->postedDate,               // postedDate
                $request->branchId,                 // branchId
                $createdDate, // CreatedDate
                $request->CreatedUser,              // CreatedUser
                NULL,                                // ModifiedDate
                NULL,                                // ModifiedUser
                // Retention fields
                $request->RetentionLedgerId,
                $request->RetentionType,
                $request->RetentionDueDate,
                $request->RetentionPercentage,
                $request->RetentionAmount,
                $request->GodownId,                 // GodownId
                $documents                           // documents (JSON array of file paths)
            ]);

            // dd($result);
            $purchase = PurchaseMaster::find($result[0]->out_purchasemasterid);

            // Insert into purchaseDetails
            if ($request->has('purchaseDetails')) {
                foreach ($request->purchaseDetails as $sd) {
                    $result = DB::select('SELECT public.insert_purchase_details1(
						?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
					) AS out_purchaseDetails1Id', [
                        (PurchaseDetails1::max('purchaseDetails1Id') ?? 0) + 1,
                        $purchase->purchaseMasterId,
                        $sd['receiptDetails1Id'],
                        $sd['orderDetails1Id'],
                        $sd['productCode'],
                        $sd['productDescription'],
                        $sd['qty'],
                        $sd['freeQty'],
                        $sd['rate'],
                        $sd['unitId'],
                        $sd['discountPercentage'],
                        $sd['taxId'],
                        $sd['taxType'],
                        $sd['taxAmount'],
                        $sd['grossAmount'],
                        $sd['netAmount'],
                        $sd['amount'],
                        $sd['SalesRate'],
                        $sd['SlNo'],
                        $sd['ConversionFactor'],
                        $sd['barcode'],
                        $sd['billDiscOnProduct'],
                        $sd['AddCostonProduct'],
                        $sd['otherchargeOnProduct'],
                        $sd['GodownId'],
                        $sd['RackId'],
                        $request->branchId,
                        $createdDate,
                        $request->CreatedUser,
                        NULL, // ModifiedDate
                        NULL  // ModifiedUser
                    ]);
                    // Update Product Purchase Rate
                    $qty = $sd['qty'];
                    $unitConversionRate = $sd['ConversionFactor'];

                    // if ($qty > 0 && $unitConversionRate > 0) {
                    $newPurchasePrice = ($sd['amount'] - $sd['taxAmount']) / $qty / $unitConversionRate;

                    Product::where('productCode', $sd['productCode'])
                        ->update([
                            'purchaseRate' => $newPurchasePrice,
                        ]);
                    // }
                    // $existingPurchasePrice = ProductPurchasePrice::where('ledgerId', $purchase->ledgerId)
                    //     ->where('productCode', $sd['productCode'])
                    //     ->where('unitId', $sd['unitId'])
                    //     ->first();
                    $existingPurchasePrice = ProductPurchasePrice::where('ledgerid', $purchase->ledgerId)
                        ->where('productcode', $sd['productCode'])
                        ->where('unitid', $sd['unitId'])
                        ->first();
                    $purchasePriceData = [
                        'ledgerid' => $purchase->ledgerId,
                        'productcode' => $sd['productCode'],
                        'unitid' => $sd['unitId'],
                        'amount' => $sd['amount'],
                        'discpercentage' => $sd['discountPercentage'] ?? 0,
                        'discamount' => ($sd['amount'] * ($sd['discountPercentage'] ?? 0)) / 100,
                        'purchaseprice' => $newPurchasePrice,
                        'userid' => $request->CreatedUser,
                        'branchid' => $request->branchId,
                        'purchasedate' => $purchase->date,
                        'purchasemasterid' => $purchase->purchaseMasterId,
                        'extradate' => $createdDate,
                    ];
                    if ($existingPurchasePrice) {
                        $existingPurchasePrice->update($purchasePriceData);
                    } else {
                        $maxId = ProductPurchasePrice::selectRaw(
                            'COALESCE(MAX(CAST(purchasepriceid AS INTEGER)), 0) as max_id'
                        )->value('max_id');

                        $purchasePriceData['purchasepriceid'] = (string) ($maxId + 1);

                        ProductPurchasePrice::create($purchasePriceData);
                    }
                    if ($existingPurchasePrice) {
                        // Update existing record
                        $existingPurchasePrice->update($purchasePriceData);
                    } else {
                        // Create new record with auto-generated ID
                        $maxId = ProductPurchasePrice::max('purchasepriceid') ?? 0;
                        $purchasePriceData['purchasepriceid'] = $maxId + 1;
                        ProductPurchasePrice::create($purchasePriceData);
                    }
                    $productCategory = strtolower(trim($sd['category'] ?? ''));

                    if ($productCategory !== 'non inventory') {

                        $inwardQty = ($sd['qty'] + ($sd['freeQty'] ?? 0)) * $sd['ConversionFactor'];
                        $voucherQty = ($sd['qty'] + ($sd['freeQty'] ?? 0));

                        DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                            $purchase->date,
                            'Purchase Invoice',
                            $purchase->purchaseMasterId,
                            $sd['productCode'],
                            $purchase->BatchId,
                            $sd['GodownId'],
                            $sd['RackId'],
                            $voucherQty,
                            $sd['unitId'],
                            $sd['rate'],
                            $inwardQty,
                            0,
                            $sd['rate'],
                            $sd['unitId'],
                            null,
                            null,
                            $request->branchId,
                            $request->CreatedUser,
                            $createdDate
                        ]);
                    }
                }
            }



            if ($purchase->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $purchase->ledgerId,          // ledgerId
                    0,                            // debit
                    $purchase->totalAmount,     // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,       // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->totalAmount > 0) {
                $roundOff = $purchase->roundOff ?? 0;

                if ($roundOff < 0) {
                    $purchaseDebit = $purchase->totalAmount - $purchase->totalTax - abs($roundOff);
                } else {
                    $purchaseDebit = $purchase->totalAmount - $purchase->totalTax + $roundOff;
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $purchase->purchaseAccount,  // ledgerId
                    $purchaseDebit,             // debit
                    0,                            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // roundOff Posting
            if ($purchase->roundOff != 0) {
                $roundOff = $purchase->roundOff ?? 0;

                if ($roundOff > 0) {
                    $credit = 0;
                    $debit = $roundOff;
                } else {
                    $debit = 0;
                    $credit = abs($roundOff);
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    29,   // Round Off ledgerId
                    $debit,                      // debit
                    $credit,                    // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->totalTax > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $request->vatLedgerId,  // ledgerId
                    $purchase->totalTax,        // debit
                    0,                            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->otherCharge > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $request->otherChargeLedgerId,  // ledgerId
                    $purchase->otherCharge,         // debit
                    0,                            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,
                    $createdDate
                ]);
            }

            if ($purchase->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $request->CashLedgerId,  // ledgerId
                    0,                      // debit
                    $purchase->CashAmount,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->BankAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $request->BankLedgerId,  // ledgerId
                    0,                      // debit
                    $purchase->BankAmount,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->BankAmount > 0 || $purchase->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $request->ledgerId,  // ledgerId
                    ($purchase->BankAmount + $purchase->CashAmount),     // debit
                    0,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // Partybalance Posting
            $result = DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $purchase->date,                     // date
                $purchase->ledgerId,                 // ledgerId
                'Purchase Invoice',                  // voucherType
                $purchase->purchaseMasterId,             // voucherNo
                'NA',                           // againstVoucherType
                'NA',                           // againstvoucherNo
                'New',                           // referenceType
                0,                                         // debit
                $purchase->BillBalanceAmount,          // credit
                $purchase->creditPeriod,             // creditPeriod
                $purchase->currencyConversionId,   // currencyConversionId
                $purchase->invoiceNo,                 // invoiceNo
                $purchase->RefNo,                  // referenceNo
                $purchase->totalAmount,             // BillAmount
                $purchase->date,                    // invoiceDate
                $purchase->costCentreId,              // costCentreId
                $purchase->exchangeRate,              // exchangeRate
                $purchase->exchangeDate,              // exchangeDate
                $purchase->branchId,                  // branchId
                $request->CreatedUser,              // CreatedUser
                $createdDate
            ]);
            $master = PurchaseMaster::find($purchase->purchaseMasterId);

            // $details = PurchaseDetails1::where(
            //     'purchaseMasterId',
            //     $purchase->purchaseMasterId
            // )->get();
            $details = DB::table('tbl_PurchaseDetails1 as pd')
                ->leftJoin('tbl_Product as p', 'pd.productCode', '=', 'p.productCode')
                ->where('pd.purchaseMasterId', $purchase->purchaseMasterId)
                ->select(
                    'pd.*',
                    'p.productName as productName',
                    'p.productNameArb as productNameArabic' // ✅ CORRECT COLUMN

                )
                ->get();

            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Purchase Invoice insert',
                'Purchase Invoice',
                'INSERTED',
                $request->branchId,
                $request->CreatedUser ?? 'System',
                $purchase->purchaseMasterId,
                $purchase->voucherNo ?? null,
                $purchase->date,
                null,
                $purchase->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 201,
                'error'  => false,
                'message' => 'Purchase Invoice Inserted Successfully',
                'data' => [
                    'purchaseMasterId' => $master->purchaseMasterId,
                    'invoiceNo'        => $master->invoiceNo,
                    'documents'        => $master->documents
                        ? json_decode($master->documents, true)
                        : [],

                    'payload' => [
                        'purchaseMaster' => [
                            ...$master->toArray(),
                            'purchaseDetails' => $details->toArray()
                        ]
                    ]
                ]
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
     * Update Purchase Invoice
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $purchase = PurchaseMaster::find($id);

            if (!$purchase) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Purchase Invoice Not Found',
                ], 404);
            }
            // Check duplicate Vendor Invoice No + Ledger,
            // excluding the current purchase being updated
            $vendorInvoiceNo = trim($request->vendorInvoiceNo ?? '');

            if ($vendorInvoiceNo !== '' && !empty($request->ledgerId)) {

                $exists = DB::table('tbl_Purchasemaster')
                    ->where('vendorInvoiceNo', $vendorInvoiceNo)
                    ->where('ledgerId', $request->ledgerId)
                    ->where('purchaseMasterId', '!=', $id)
                    ->exists();

                if ($exists) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 409,
                        'error' => true,
                        'message' => 'Vendor Invoice No already exists for this supplier.'
                    ], 409);
                }
            }
            $oldValue = $purchase->totalAmount;

            // Existing documents JSON (column is "Documents" with capital D)
            $documents = $purchase->Documents;

            if ($request->hasFile('documents')) {

                $request->validate([
                    'documents'   => 'required|array',
                    'documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx|max:20480'
                ]);

                // Delete old files
                $oldFiles = json_decode($purchase->Documents, true);

                if (is_array($oldFiles)) {
                    foreach ($oldFiles as $oldFile) {
                        if ($oldFile && file_exists(public_path($oldFile))) {
                            unlink(public_path($oldFile));
                        }
                    }
                }

                $destinationPath = public_path('storage/purchase_documents');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $filePaths = [];

                foreach ($request->file('documents') as $file) {

                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

                    $file->move($destinationPath, $fileName);

                    $filePaths[] = 'storage/purchase_documents/' . $fileName;
                }

                $documents = json_encode($filePaths);
            }
            $branchId = $request->input('branchId', 1);
            $modifiedDate = getBranchNow($branchId);
            DB::statement('SELECT public.update_purchase_master(
			?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?, ?
		)', [
                $id,
                $request->vendorInvoiceNo,
                $request->date,
                $request->invoiceDate,
                $request->creditPeriod,
                $request->ledgerId,
                $request->partyName,
                $request->partyAddress,
                $request->partyPhone,
                $request->RefNo,
                $request->partyRefDate,
                $request->partyVatNo,
                $request->narration,
                $request->purchaseAccount,
                $request->receiptMasterId,
                $request->orderMasterId,
                $request->AgainstNo,
                $request->costCentreId,
                $request->BatchId,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->taxType,
                $request->paymentMode,
                $request->subTotal,
                $request->additionalCost,
                $request->billDiscount,
                $request->othercharge,
                $request->otherChargeLedgerId,
                $request->taxableAmt,
                $request->totalTax,
                $request->roundoff,
                $request->totalAmount,
                $request->transportCompany,
                $request->CashLedgerId,
                $request->CashRefNo,
                $request->CashAmount,
                $request->BankLedgerId,
                $request->BankRefNo,
                $request->BankAmount,
                $request->BillBalanceAmount,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->branchId,
                $modifiedDate,
                $request->ModifiedUser,
                $request->RetentionLedgerId,
                $request->RetentionType,
                $request->RetentionDueDate,
                $request->RetentionPercentage ?? 0,
                $request->RetentionAmount ?? 0,
                $request->GodownId,
                $documents
            ]);

            $purchase->refresh(); // reload the updated values from the DB

            if ($request->has('purchaseDetails')) {
                DB::select('SELECT delete_purchase_details1_byid(?)', [$purchase->purchaseMasterId]);

                DB::select('SELECT delete_stock_posting(?, ?)', [
                    'Purchase Invoice', // voucher_type
                    $purchase->purchaseMasterId // voucherNo
                ]);

                foreach ($request->purchaseDetails as $sd) {
                    $result = DB::select('SELECT public.insert_purchase_details1(
						?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
					) AS out_purchaseDetails1Id', [
                        (PurchaseDetails1::max('purchaseDetails1Id') ?? 0) + 1,
                        $purchase->purchaseMasterId,
                        $sd['receiptDetails1Id'],
                        $sd['orderDetails1Id'],
                        $sd['productCode'],
                        $sd['productDescription'],
                        $sd['qty'],
                        $sd['freeQty'],
                        $sd['rate'],
                        $sd['unitId'],
                        $sd['discountPercentage'],
                        $sd['taxId'],
                        $sd['taxType'],
                        $sd['taxAmount'],
                        $sd['grossAmount'],
                        $sd['netAmount'],
                        $sd['amount'],
                        $sd['SalesRate'],
                        $sd['SlNo'],
                        $sd['ConversionFactor'],
                        $sd['barcode'],
                        $sd['billDiscOnProduct'],
                        $sd['AddCostonProduct'],
                        $sd['otherchargeOnProduct'],
                        $sd['GodownId'],
                        $sd['RackId'],
                        $request->branchId,
                        $purchase->CreatedDate,
                        $purchase->CreatedUser,
                        $modifiedDate, // ModifiedDate
                        $request->ModifiedUser
                    ]);

                    // // Update Product Purchase Rate
                    $qty = $sd['qty'];
                    $unitConversionRate = $sd['ConversionFactor'];
                    $newPurchasePrice = ($sd['amount'] - $sd['taxAmount']) / $qty / $unitConversionRate;

                    Product::where('productCode', $sd['productCode'])
                        ->update([
                            'purchaseRate' => $newPurchasePrice,
                        ]);
                    $existingPurchasePrice = ProductPurchasePrice::where('ledgerid', $purchase->ledgerId)
                        ->where('productcode', $sd['productCode'])
                        ->where('unitid', $sd['unitId'])
                        ->first();
                    $purchasePriceData = [
                        'ledgerid' => $purchase->ledgerId,
                        'productcode' => $sd['productCode'],
                        'unitid' => $sd['unitId'],
                        'amount' => $sd['amount'],
                        'discpercentage' => $sd['discountPercentage'] ?? 0,
                        'discamount' => ($sd['amount'] * ($sd['discountPercentage'] ?? 0)) / 100,
                        'purchaseprice' => $newPurchasePrice,
                        'userid' => $request->ModifiedUser,
                        'branchid' => $request->branchId,
                        'purchasedate' => $purchase->date,
                        'purchasemasterid' => $purchase->purchaseMasterId,
                        'extradate' =>  $modifiedDate,
                    ];

                    if ($existingPurchasePrice) {
                        $existingPurchasePrice->update($purchasePriceData);
                    } else {
                        $maxId = ProductPurchasePrice::max('purchasepriceid') ?? 0;
                        $purchasePriceData['purchasepriceid'] = $maxId + 1;
                        ProductPurchasePrice::create($purchasePriceData);
                    }
                    $productCategory = strtolower(trim($sd['category'] ?? ''));

                    if ($productCategory !== 'non inventory') {

                        $inwardQty = ($sd['qty'] + ($sd['freeQty'] ?? 0)) * ($sd['ConversionFactor'] ?? 1);
                        $voucherQty = ($sd['qty'] + ($sd['freeQty'] ?? 0));

                        DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                            $purchase->date,
                            'Purchase Invoice',
                            $purchase->purchaseMasterId,
                            $sd['productCode'],
                            $purchase->BatchId,
                            $sd['GodownId'],
                            $sd['RackId'],
                            $voucherQty,
                            $sd['unitId'],
                            $sd['rate'],
                            $inwardQty,
                            0,
                            $sd['rate'],
                            $sd['unitId'],
                            null,
                            null,
                            $request->branchId,
                            $request->ModifiedUser,
                            $modifiedDate
                        ]);
                    }
                }
            }

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Purchase Invoice', // voucherType
                $purchase->purchaseMasterId, // voucherNo
                $request->branchId   // branchId
            ]);

            if ($purchase->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $purchase->ledgerId,          // ledgerId
                    0,                            // debit
                    $purchase->totalAmount,     // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,       // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->totalAmount > 0) {
                $roundOff = $purchase->roundOff ?? 0;

                if ($roundOff < 0) {
                    $purchaseDebit = $purchase->totalAmount - $purchase->totalTax - abs($roundOff);
                } else {
                    $purchaseDebit = $purchase->totalAmount - $purchase->totalTax + $roundOff;
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $purchase->purchaseAccount,  // ledgerId
                    $purchaseDebit,             // debit
                    0,                            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate

                ]);
            }

            // roundOff Posting
            if ($purchase->roundOff != 0) {
                $roundOff = $purchase->roundOff ?? 0;

                if ($roundOff > 0) {
                    $credit = 0;
                    $debit = $roundOff;
                } else {
                    $debit = 0;
                    $credit = abs($roundOff);
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    29,   // Round Off ledgerId
                    $debit,                      // debit
                    $credit,                    // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->totalTax > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $request->vatLedgerId,  // ledgerId
                    $purchase->totalTax,        // debit
                    0,                            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,
                    $modifiedDate

                ]);
            }

            if ($purchase->otherCharge > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $request->otherChargeLedgerId,  // ledgerId
                    $purchase->otherCharge,         // debit
                    0,                            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $request->CashLedgerId,  // ledgerId
                    0,                      // debit
                    $purchase->CashAmount,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->BankAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $request->BankLedgerId,  // ledgerId
                    0,                      // debit
                    $purchase->BankAmount,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->BankAmount > 0 || $purchase->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Invoice',         // voucherType
                    $purchase->purchaseMasterId,  // voucherNo
                    $request->ledgerId,  // ledgerId
                    ($purchase->BankAmount + $purchase->CashAmount),     // debit
                    0,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            // Partybalance Posting
            $result = DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $purchase->date,                     // date
                $purchase->ledgerId,                 // ledgerId
                'Purchase Invoice',                  // voucherType
                $purchase->purchaseMasterId,             // voucherNo
                'NA',                           // againstVoucherType
                'NA',                           // againstvoucherNo
                'New',                           // referenceType
                0,                                         // debit
                $purchase->BillBalanceAmount,          // credit
                $purchase->creditPeriod,             // creditPeriod
                $purchase->currencyConversionId,   // currencyConversionId
                $purchase->invoiceNo,                 // invoiceNo
                $purchase->RefNo,                  // referenceNo
                $purchase->totalAmount,             // BillAmount
                $purchase->date,                    // invoiceDate
                $purchase->costCentreId,              // costCentreId
                $purchase->exchangeRate,              // exchangeRate
                $purchase->exchangeDate,              // exchangeDate
                $purchase->branchId,                  // branchId
                $request->ModifiedUser,              // CreatedUser
                $modifiedDate
            ]);

            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Purchase Invoice Update',
                'Purchase Invoice',
                'UPDATED',
                $purchase->branchId,
                $request->ModifiedUser ?? 'System',
                $purchase->purchaseMasterId,
                $purchase->voucherNo ?? null,
                $purchase->date,
                $oldValue,
                $purchase->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Purchase Invoice Updated Successfully'
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
     * Delete Purchase Invoice
     */
    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            /* -------------------------------------------------
         | 1. FIND PURCHASE MASTER
         |-------------------------------------------------*/
            $purchase = PurchaseMaster::where('purchaseMasterId', $id)->first();

            if (!$purchase) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Purchase Invoice Not Found',
                ], 404);
            }

            $oldValue = $purchase->totalAmount;

            /* -------------------------------------------------
         | 2. CHECK IF PURCHASE RETURN EXISTS
         |-------------------------------------------------*/
            $isUsedInReturn = DB::table('tbl_PurchaseReturnDetails1')
                ->whereIn('purchaseDetails1Id', function ($q) use ($purchase) {
                    $q->select('purchaseDetails1Id')
                        ->from('tbl_PurchaseDetails1')
                        ->where('purchaseMasterId', $purchase->purchaseMasterId);
                })
                ->exists();

            if ($isUsedInReturn) {
                return response()->json([
                    'status' => 400,
                    'error' => true,
                    'message' => 'Cannot delete Purchase Invoice. Purchase Return already exists.'
                ], 400);
            }

            /* -------------------------------------------------
         | 3. DELETE DEPENDENT TABLES (VERY IMPORTANT ORDER)
         |-------------------------------------------------*/

            /*
         | ✅ 3.1 DELETE Product Purchase Price
         | (Correct column: purchasemasterid)
         */
            DB::table('tbl_productpurchaseprice')
                ->where('purchasemasterid', $purchase->purchaseMasterId)
                ->delete();


            /*
         | ✅ 3.2 DELETE Purchase Details
         */
            DB::table('tbl_PurchaseDetails1')
                ->where('purchaseMasterId', $purchase->purchaseMasterId)
                ->delete();


            /*
         | ✅ 3.3 DELETE Stock Posting
         */
            DB::select('SELECT delete_stock_posting(?, ?)', [
                'Purchase Invoice',
                $purchase->purchaseMasterId
            ]);


            /*
         | ✅ 3.4 DELETE Ledger Posting
         */
            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Purchase Invoice',
                $purchase->purchaseMasterId,
                null
            ]);


            /*
         | ✅ 3.5 DELETE Party Balance
         */
            DB::select('SELECT delete_partybalance(?, ?)', [
                'Purchase Invoice',
                $purchase->purchaseMasterId
            ]);


            /* -------------------------------------------------
         | 4. VERIFY CHILD RECORDS ARE DELETED (Safety Check)
         |-------------------------------------------------*/
            $remainingChild = DB::table('tbl_productpurchaseprice')
                ->where('purchasemasterid', $purchase->purchaseMasterId)
                ->exists();

            if ($remainingChild) {
                throw new \Exception('Child records still exist. Deletion aborted.');
            }


            /* -------------------------------------------------
         | 5. DELETE MASTER
         |-------------------------------------------------*/
            $purchase->delete();


            /* -------------------------------------------------
         | 6. ACTIVITY LOG
         |-------------------------------------------------*/
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Purchase Invoice Delete',
                'Purchase Invoice',
                'DELETED',
                $purchase->branchId,
                $request->ModifiedUser ?? 'System',
                $purchase->purchaseMasterId,
                $purchase->voucherNo,
                $purchase->date,
                $oldValue,
                0
            );


            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Purchase Invoice Deleted Successfully',
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


    public function purchasereport(Request $request)
    {
        DB::beginTransaction();

        try {

            $ledgerId        = ($request->ledgerId == 0)        ? null : $request->ledgerId;
            $costCentreId    = ($request->costCentreId == 0)    ? null : $request->costCentreId;
            $orderMasterId   = ($request->orderMasterId == 0)   ? null : $request->orderMasterId;
            $receiptMasterId = ($request->receiptMasterId == 0) ? null : $request->receiptMasterId;

            $result = DB::select(
                'SELECT * FROM public.purchase_invoice_report_fill_detail(
                ?::timestamp,
                ?::timestamp,
                ?::integer,
                ?::integer,
                ?::integer,
                ?::integer,
                ?::integer,
                ?::varchar,
                ?::timestamp,
                ?::boolean,
                ?::bigint,
                ?::bigint,
                ?::boolean,
                ?::varchar,
                ?::varchar
            )',
                [
                    $request->fromDate,
                    $request->toDate,
                    $ledgerId,
                    $request->branchId,
                    $orderMasterId,
                    $receiptMasterId,
                    $request->currencyId,
                    $request->condition ?? 'All',
                    $request->dueOn,
                    $request->isAccountOnly ?? false,
                    $request->userId ?? 0,
                    $costCentreId,
                    $request->isAccountsPosting ?? false,
                    $request->paymentMode ?? 'All',
                    $request->mode ?? 'Detailed'
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Purchase report retrieved successfully',
                'data'    => $result
            ], 200);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function purchasedayreport(Request $request)
    {
        DB::beginTransaction();

        try {

            $branchId   = $request->branchId !== null ? (int) $request->branchId : null;

            $result = DB::select(
                'SELECT * from public.purchase_invoice_day_report_fill(?,?,?,?,?)',
                [
                    $request->fromDate,
                    $request->toDate,
                    $branchId,
                    $request->createdUser,
                    $request->isAccountsPosting,

                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'purchase day report retrieved Successfully',
                'data'   => $result
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function allPurchaseApis(Request $request)
    {
        try {

            $voucherType = $request->input('voucherType', 'Sales Invoice');
            $branchId = $request->input('branchId', null);
            $yearId = $request->input('yearId', null);

            if (!$branchId) {
                return response()->json([
                    'status' => 400,
                    'error'  => true,
                    'message' => 'Branch ID is required',
                    'data'   => []
                ], 400);
            }

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);

            $employees = DB::select('SELECT * FROM public.get_all_employee()');
            $transactionbatch = DB::select('SELECT * FROM fn_get_transaction_batch_by_vouchertype(?,?)', [$voucherType, $branchId]);
            $costcentre = DB::select('SELECT * FROM public.get_all_costcentres()');
            $pricinglevel = DB::select('SELECT * FROM public.get_all_pricing_level()');

            $ledgerTypes = $request->input('ledgerTypes', []);

            if (empty($ledgerTypes)) {
                return response()->json([
                    'status' => 400,
                    'error' => true,
                    'message' => 'Not Found ledger Type',
                    'data' => []
                ], 400);
            }

            $customersupplierLedgers = AccountLedger::whereIn('ledgerType', $ledgerTypes)
                ->whereRaw(
                    'EXISTS (
                                SELECT 1
                                FROM jsonb_array_elements("branchId") AS b
                                WHERE (b->>\'branchId\')::int = ?
                            )',
                    [$branchId]
                )
                ->orderBy('ledgerName', 'asc')
                ->get();
            $ismain_branch = Branch::where('branchId', $branchId)->value('mainBranch');

            if ($ismain_branch == true) {
                $godowns = DB::select('SELECT * FROM public.get_all_godown(?)', [null]);
            } else {
                $godowns = DB::select('SELECT * FROM public.get_all_godown(?)', [$branchId]);
            }

            $ledgerId = $request->input('ledgerId');
            $currencyId = $request->input('currencyId');

            // Call PostgreSQL function
            $LedgerBalance = DB::select("
                SELECT * FROM get_current_ledger_balance(?, ?, ?)
            ", [$ledgerId, $branchId, $currencyId]);


            // $groupIds = $request->input('group_ids', []);
            $groupIds3 = [23];
            // Create placeholders for safe parameter binding
            $placeholders3 = implode(',', array_fill(0, count($groupIds3), '?'));
            $sql3 = "
                WITH RECURSIVE GroupInMainGroupP AS (
                    -- Base case
                    SELECT
                        \"groupId\",
                        \"groupUnder\",
                        1 AS \"HierarchyLevel\",
                        ARRAY[\"groupId\"] AS path
                    FROM \"tbl_AccountGroup\"
                    WHERE \"groupId\" IN ($placeholders3)

                    UNION ALL

                    -- Recursive case
                    SELECT
                        e.\"groupId\",
                        e.\"groupUnder\",
                        g.\"HierarchyLevel\" + 1,
                        g.path || e.\"groupId\"
                    FROM \"tbl_AccountGroup\" e
                    JOIN GroupInMainGroupP g
                        ON e.\"groupUnder\" = g.\"groupId\"
                    WHERE
                        g.\"HierarchyLevel\" < 10
                        AND NOT e.\"groupId\" = ANY (g.path)
                )

                SELECT DISTINCT
                    A.\"groupId\",
                    A.\"accountGroupName\",
                    A.\"groupUnder\"
                FROM \"tbl_AccountGroup\" A
                JOIN GroupInMainGroupP G
                    ON A.\"groupId\" = G.\"groupId\"
                ORDER BY A.\"accountGroupName\" ASC
                ";

            // Run query with bindings
            $result3 = \DB::select($sql3, $groupIds3);
            // Extract group IDs from result
            $excludedGroupIds3 = collect($result3)->pluck('groupId')->toArray();

            $purchaseaccount = AccountLedger::whereIn('groupId', $excludedGroupIds3)
                ->whereRaw(
                    'EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements("branchId") AS b
                    WHERE (b->>\'branchId\')::int = ?
                )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();




            // $groupIds = $request->input('group_ids', []);
            $groupIds = [5, 6];
            if (empty($groupIds)) {
                return response()->json([
                    'status' => 400,
                    'error' => true,
                    'message' => 'No group IDs provided',
                    'data' => []
                ], 400);
            }

            // Create placeholders for safe parameter binding
            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
            $sql = "
                WITH RECURSIVE GroupInMainGroupP AS (
                    -- Base case
                    SELECT
                        \"groupId\",
                        \"groupUnder\",
                        1 AS \"HierarchyLevel\",
                        ARRAY[\"groupId\"] AS path
                    FROM \"tbl_AccountGroup\"
                    WHERE \"groupId\" IN ($placeholders)

                    UNION ALL

                    -- Recursive case
                    SELECT
                        e.\"groupId\",
                        e.\"groupUnder\",
                        g.\"HierarchyLevel\" + 1,
                        g.path || e.\"groupId\"
                    FROM \"tbl_AccountGroup\" e
                    JOIN GroupInMainGroupP g
                        ON e.\"groupUnder\" = g.\"groupId\"
                    WHERE
                        g.\"HierarchyLevel\" < 10
                        AND NOT e.\"groupId\" = ANY (g.path)
                )

                SELECT DISTINCT
                    A.\"groupId\",
                    A.\"accountGroupName\",
                    A.\"groupUnder\"
                FROM \"tbl_AccountGroup\" A
                JOIN GroupInMainGroupP G
                    ON A.\"groupId\" = G.\"groupId\"
                ORDER BY A.\"accountGroupName\" ASC
                ";

            // Run query with bindings
            $result = \DB::select($sql, $groupIds);
            // Extract group IDs from result
            $excludedGroupIds = collect($result)->pluck('groupId')->toArray();

            $bank = AccountLedger::whereIn('groupId', $excludedGroupIds)
                ->whereRaw(
                    'EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements("branchId") AS b
                    WHERE (b->>\'branchId\')::int = ?
                )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();




            $groupIds2 = [8];
            // Create placeholders for safe parameter binding
            $placeholders2 = implode(',', array_fill(0, count($groupIds2), '?'));
            $sql2 = "
                WITH RECURSIVE GroupInMainGroupP AS (
                    -- Base case
                    SELECT
                        \"groupId\",
                        \"groupUnder\",
                        1 AS \"HierarchyLevel\",
                        ARRAY[\"groupId\"] AS path
                    FROM \"tbl_AccountGroup\"
                    WHERE \"groupId\" IN ($placeholders2)

                    UNION ALL

                    -- Recursive case
                    SELECT
                        e.\"groupId\",
                        e.\"groupUnder\",
                        g.\"HierarchyLevel\" + 1,
                        g.path || e.\"groupId\"
                    FROM \"tbl_AccountGroup\" e
                    JOIN GroupInMainGroupP g
                        ON e.\"groupUnder\" = g.\"groupId\"
                    WHERE
                        g.\"HierarchyLevel\" < 10
                        AND NOT e.\"groupId\" = ANY (g.path)
                )

                SELECT DISTINCT
                    A.\"groupId\",
                    A.\"accountGroupName\",
                    A.\"groupUnder\"
                FROM \"tbl_AccountGroup\" A
                JOIN GroupInMainGroupP G
                    ON A.\"groupId\" = G.\"groupId\"
                ORDER BY A.\"accountGroupName\" ASC
                ";

            // Run query with bindings
            $result2 = \DB::select($sql2, $groupIds2);
            // Extract group IDs from result

            $excludedGroupIds2 = collect($result2)->pluck('groupId')->toArray();

            $cash = AccountLedger::whereIn('groupId', $excludedGroupIds2)
                ->whereRaw(
                    'EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements("branchId") AS b
                    WHERE (b->>\'branchId\')::int = ?
                )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();



            $financesettings = collect(DB::select('SELECT * FROM public.get_all_finance_settings()'));

            $defaultpurchaseAccountId = $financesettings
                ->where('branchId', $branchId)
                ->first()
                ->defaultPurchaseAccount ?? null;

            $orderMasterId = $request->orderMasterId;
            $salesOrderQuotationlist = DB::select('SELECT * FROM public.get_sales_order_quotation_list(?, ?, ?)', [
                $defaultpurchaseAccountId,
                $branchId,
                $orderMasterId
            ]);

            $groupIds4 = [13, 17];
            $placeholders4 = implode(',', array_fill(0, count($groupIds4), '?'));
            $sql4 = "
        WITH RECURSIVE GroupInMainGroupP AS (
            SELECT
                \"groupId\",
                \"groupUnder\",
                1 AS \"HierarchyLevel\",
                ARRAY[\"groupId\"] AS path
            FROM \"tbl_AccountGroup\"
            WHERE \"groupId\" IN ($placeholders4)

            UNION ALL

            SELECT
                e.\"groupId\",
                e.\"groupUnder\",
                g.\"HierarchyLevel\" + 1,
                g.path || e.\"groupId\"
            FROM \"tbl_AccountGroup\" e
            JOIN GroupInMainGroupP g
                ON e.\"groupUnder\" = g.\"groupId\"
            WHERE
                g.\"HierarchyLevel\" < 10
                AND NOT e.\"groupId\" = ANY (g.path)
        )

        SELECT DISTINCT
            A.\"groupId\",
            A.\"accountGroupName\",
            A.\"groupUnder\"
        FROM \"tbl_AccountGroup\" A
        JOIN GroupInMainGroupP G
            ON A.\"groupId\" = G.\"groupId\"
        ORDER BY A.\"accountGroupName\" ASC
        ";


            $result4 = DB::select($sql4, $groupIds4);
            $excludedGroupIds4 = collect($result4)->pluck('groupId')->toArray();

            $othercharge = AccountLedger::whereIn('groupId', $excludedGroupIds4)
                ->whereRaw(
                    'EXISTS (
            SELECT 1
            FROM jsonb_array_elements("branchId") AS b
            WHERE (b->>\'branchId\')::int = ?
        )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();
            $taxMaster = DB::select('SELECT * FROM public.get_all_tax_master() ORDER BY "taxName" ASC');
            $currencies = DB::select('SELECT * FROM get_currency_with_conversion()');

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Purchase Data',
                'data' => [
                    'voucherdata'   => [
                        'table_pk'        => $codeData['table_pk'],
                        'voucherCode'    => $codeData['voucherCode'],
                        'voucherNo'       => $codeData['voucherNo'],
                        'suffixPrefixId'  => $codeData['suffixPrefixId'],
                    ],
                    'employees'     =>  $employees,
                    'transactionbatch'  =>  $transactionbatch,
                    'costcentre'    =>  $costcentre,
                    'pricinglevel'  =>  $pricinglevel,
                    'customersupplierLedgers' => $customersupplierLedgers,
                    'godowns'       => $godowns,
                    'LedgerBalance' => $LedgerBalance[0] ?? null,
                    'purchaseaccount' => $purchaseaccount,
                    'othercharge'             => $othercharge,
                    'bank' => $bank,
                    'cash' => $cash,
                    'salesOrderQuotationlist' => $salesOrderQuotationlist,
                    'taxMaster'  => $taxMaster,
                    'currencies' => $currencies,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    public function PurchaseInvoiceForReturn(Request $request)
    {
        $purchase = DB::select(
            'SELECT * FROM public.get_all_purchase_master_list(?,?,?,?,?,?,?)',
            [
                $request->fromDate,
                $request->toDate,
                null, // limits (optional)
                null, // page (optional)
                $request->branchId,
                $request->vouchercode,
                $request->ledgerid
            ]
        );

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Invoice for Return Retrieved Successfully',
            'data' => $purchase
        ]);
    }

    public function PurchaseInvoiceMaterialReceiptlist(Request $request)
    {
        $ledgerId = $request->ledgerId;
        $branchId = $request->branchId;
        $purchasemasterId = $request->purchasemasterId;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.purchase_invoice_material_receipt_list(?, ?, ?, ?)', [
            $ledgerId,
            $branchId,
            $purchasemasterId,
            $request->isaccountsposting,
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Invoice Material Receipt List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function PurchaseInvoiceMaterialReceiptDetails(Request $request)
    {
        $SalesOrderinvoices = DB::select('SELECT * FROM public.get_material_receipt_master_byid(?)', [
            $request->receiptmasterId
        ]);

        // Check if found
        if (empty($SalesOrderinvoices)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Material Receipt Not Found',
            ], 200);
        }

        // Convert to object (since DB::select returns an array)
        $SalesOrderinvoices = (array) $SalesOrderinvoices[0];

        // Step 2: Get invoice details using proformamasterid
        $SalesOrderinvoicesDetails = DB::select('SELECT * FROM public.purchase_invoice_material_receipt_details(?, ?)', [
            $SalesOrderinvoices['receiptMasterId'],
            $request->purchasemasterId
        ]);

        // Step 3: Attach invoice details to master data
        $SalesOrderinvoices['purchaseDetails'] = $SalesOrderinvoicesDetails;

        // Step 4: Return combined JSON response
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Invoice Material Receipt Details Retrieved Successfully',
            'data' => $SalesOrderinvoices,
        ], 200);
    }

    public function PurchaseInvoiceAgainstOrderlist(Request $request)
    {
        $ledgerId = $request->ledgerId;
        $branchId = $request->branchId;
        $purchasemasterId = $request->purchasemasterId;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.purchase_invoice_against_order_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $purchasemasterId
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Invoice Against Order List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function PurchaseInvoiceAgainstOrderDetails(Request $request)
    {
        $SalesOrderinvoices = DB::select('SELECT * FROM public.get_purchase_order_master_data_byid(?)', [
            $request->orderMasterId
        ]);

        // Check if found
        if (empty($SalesOrderinvoices)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Purchase Order Not Found',
            ], 404);
        }

        // Convert to object (since DB::select returns an array)
        $SalesOrderinvoices = (array) $SalesOrderinvoices[0];

        // Step 2: Get invoice details using proformamasterid
        $SalesOrderinvoicesDetails = DB::select('SELECT * FROM public.purchase_invoice_against_order_details(?, ?)', [
            $SalesOrderinvoices['orderMasterId'],
            $request->purchasemasterId
        ]);

        // Step 3: Attach invoice details to master data
        $SalesOrderinvoices['orderDetails'] = $SalesOrderinvoicesDetails;

        // Step 4: Return combined JSON response
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Invoice Against Order Details Retrieved Successfully',
            'data' => $SalesOrderinvoices,
        ], 200);
    }
    public function purchaseHistory(Request $request)
    {
        try {
            $data = DB::select(
                'SELECT * FROM public.fn_purchase_history(?, ?)',
                [
                    $request->productCode,
                    $request->branchId
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Purchase history fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching purchase history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSupplierLastPurchaseRate(Request $request)
    {
        try {
            $result = DB::selectOne(
                'SELECT fn_get_supplier_last_purchase_rate(?, ?, ?) AS "lastPurchaseRate"',
                [
                    $request->ledgerId,
                    $request->productCode,
                    $request->branchId
                ]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'ledgerId' => (int) $request->ledgerId,
                    'productCode' => $request->productCode,
                    'branchId' => (int) $request->branchId,
                    'lastPurchaseRate' => (float) ($result->lastPurchaseRate ?? 0),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to get last purchase rate.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}