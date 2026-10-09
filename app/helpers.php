<?php

use Illuminate\Support\Facades\DB;
use App\Models\SuffixPrefix;

if (! function_exists('voucherNoGeneration')) {
    function voucherNoGeneration(string $voucherType, int $branchId, int $yearId = null, string $taxType = null): array
    {
        // Map voucherType → table + primary key
        $tableMap = [
            'Employee'           => ['table' => 'tbl_Employee',       'pk' => 'employeeId'],
            'Sales Invoice'      => ['table' => 'tbl_SalesMaster',    'pk' => 'salesMasterId'],
            'Sales Return'       => ['table' => 'tbl_SalesReturnMaster', 'pk' => 'ReturnMasterId'],
            'Sales Invoice-No Tax' => ['table' => 'tbl_SalesMaster',    'pk' => 'salesMasterId'],
            'Sales Quotation'    => ['table' => 'tbl_QuotationMaster', 'pk' => 'quotationMasterId'],
            'Sales Order'        => ['table' => 'tbl_SalesOrderMaster', 'pk' => 'orderMasterId'],
            'Receipt Voucher'    => ['table' => 'tbl_ReceiptMaster', 'pk' => 'receiptMasterId'],
            'Proforma Invoice'   => ['table' => 'tbl_proformaInvoiceMaster', 'pk' => 'proformaMasterId'],
            'Delivery Note'      => ['table' => 'tbl_DeliveryNoteMaster', 'pk' => 'deliveryNoteMasterId'],
            'Payment Voucher'    => ['table' => 'tbl_PaymentMaster', 'pk' => 'paymentMasterId'],
            'Contra Voucher'     => ['table' => 'tbl_ContraMaster', 'pk' => 'contraMasterId'],
            'Journal Voucher'    => ['table' => 'tbl_JournalMaster', 'pk' => 'JournalMasterId'],
            'Purchase Order'     => ['table' => 'tbl_PurchaseOrderMaster', 'pk' => 'orderMasterId'],
            'Purchase Invoice'   => ['table' => 'tbl_Purchasemaster', 'pk' => 'purchaseMasterId'],
            'Purchase Return'   => ['table' => 'tbl_PurchaseReturnMaster', 'pk' => 'returnMasterId'],
            'Material Receipt'   => ['table' => 'tbl_MaterialReceiptMaster', 'pk' => 'receiptMasterId'],
            'Physical Stock'   => ['table' => 'tbl_PhysicalStockMaster', 'pk' => 'physicalStockMasterId'],
            'Damage Stock'   => ['table' => 'tbl_DamageStockMaster', 'pk' => 'damageStockMasterId'],
            'Payable Voucher'   => ['table' => 'tbl_PayableMaster', 'pk' => 'PayableMasterId'],
            'Receivable Voucher'   => ['table' => 'tbl_ReceivableMaster', 'pk' => 'ReceivableMasterId'],
            'Stock Transfer'   => ['table' => 'tbl_StockTransferMaster', 'pk' => 'transferMasterId'],
            'Used Stock'   => ['table' => 'tbl_UsedStockMaster', 'pk' => 'usedStockMasterId'],
            'Purchase Quotation'   => ['table' => 'tbl_PurchaseQuotation', 'pk' => 'quotationMasterId'],
            'Purchase Cart'   => ['table' => 'tbl_PurchaseCartMaster', 'pk' => 'PurchaseCartMasterId'],
            'ManuFacturing Journal' => ['table' => 'tbl_manufacturingjournalmaster', 'pk' => 'JournalMasterId'],
            'Sales Return-No Tax' => ['table' => 'tbl_SalesReturnMaster', 'pk' => 'ReturnMasterId'],
            'Advance Payment' => ['table' => 'tbl_SalaryAdvancePayment', 'pk' => 'advanceMasterId'],
            // add more here...
        ];

        $map = $tableMap[$voucherType] ?? null;
        if (!$map) {
            throw new \Exception("Unknown voucher type: $voucherType");
        }

        $table = $map['table'];
        $pk    = $map['pk'];

        // ---------------------------------------------------------
        // 1. Get the MAX primary key (table_pk)
        // ---------------------------------------------------------
        $tablePk = DB::table($table)->max(DB::raw("CAST(\"$pk\" AS INTEGER)"));
        $tablePk = $tablePk ? $tablePk + 1 : 1;

        

        // ---------------------------------------------------------
        // 2. Get suffix/prefix config
        // ---------------------------------------------------------
        $configQuery = SuffixPrefix::where('voucherType', $voucherType)
            ->where('branchId', $branchId);

        if ($yearId) {
            $configQuery->where('yearId', $yearId);
        }

        $config = $configQuery->first();

        $prefix        = $config->prefix ?? '';
        $suffix        = $config->suffix ?? '';
        // $startIndex    = ($config->startIndex ?? 0) - 1;
        $startIndex     = $config->startIndex ?? 0;

        $addWithZero   = $config->addWithZero ?? 0;
        $noOfZero      = $config->noOfZero ?? 0;
        $suffixPrefixId = $config->suffixPrefixId ?? 0;

        // ---------------------------------------------------------
        // 3. Get next voucherNo
        // ---------------------------------------------------------
        $voucherNoQuery = DB::table($table)->where('branchId', $branchId);

        if ($suffixPrefixId) {
            $voucherNoQuery->where('suffixPrefixId', $suffixPrefixId);
        }
        if (in_array($voucherType, ['Sales Invoice', 'Sales Return']) && !is_null($taxType)) {
            $voucherNoQuery->where('taxType', $taxType);
        }
        // $voucherNo = $voucherNoQuery->max(DB::raw('CAST("voucherNo" AS INTEGER)')) ?? 0;
        // $voucherNo += 1;
        // dd($voucherNo);
        $voucherNo = $voucherNoQuery
            ->whereRaw('"voucherNo" ~ \'^[0-9]+$\'')
            ->max(DB::raw('CAST("voucherNo" AS INTEGER)')) ?? 0;

        $voucherNo += 1;

        // ---------------------------------------------------------
        // 4. Build voucherCode
        // ---------------------------------------------------------
        $code = (string)($voucherNo + $startIndex);

        if ($addWithZero && strlen($code) <= $noOfZero) {
            $code = str_pad($code, $noOfZero, '0', STR_PAD_LEFT);
        }

        $finalCode = $prefix . $code . $suffix;

        // ---------------------------------------------------------
        // 5. Return
        // ---------------------------------------------------------
        return [
            'table_pk'       => $tablePk,        // employeeId / salesMasterId / etc.
            'voucherNo'      => $voucherNo,
            'voucherCode'    => $finalCode,
            'suffixPrefixId' => $suffixPrefixId,
        ];
    }
}