<?php

namespace App\Imports;

use App\Models\AccountLedger;
use App\Models\AccountGroup;
use App\Models\LedgerPosting;
use App\Models\Partybalance;
use App\Models\ShippingAddress;
use App\Models\LedgerDocuments;
use Maatwebsite\Excel\Concerns\{
    OnEachRow,
    WithHeadingRow,
    WithChunkReading,
    WithValidation
};
use Maatwebsite\Excel\Row;

class AccountLedgerBulkImport implements
    OnEachRow,
    WithHeadingRow,
    WithChunkReading,
    WithValidation
{
    public function onRow(Row $excelRow)
    {
        $row = $excelRow->toArray();

        $ledgerCode = trim($row['ledgercode']);

        $ledger = AccountLedger::where('ledgerCode', $ledgerCode)->first();

        $isNew = false;

        if (!$ledger) {
            $ledger = new AccountLedger();
            $ledger->ledgerId = (AccountLedger::max('ledgerId') ?? 0) + 1;
            $ledger->CreatedDate = now();
            $ledger->CreatedUser = 'BulkUpload';
            $isNew = true;
        } else {
            $ledger->ModifiedDate = now();
            $ledger->ModifiedUser = 'BulkUpload';
        }

        /* ===============================
           ✅ NORMALIZE VALUES
        =============================== */

        // ✅ Bill By Bill
        $billValue = strtolower(trim($row['billbybill']));
        if (in_array($billValue, ['1', 'yes', 'true'])) {
            $billByBill = 1;
        } elseif (in_array($billValue, ['0', 'no', 'false'])) {
            $billByBill = 0;
        } else {
            throw new \Exception("Invalid billbybill value for ledger: $ledgerCode");
        }

        // ✅ Cr / Dr
        $crOrDr = strtoupper(trim($row['crordr']));
        if (!in_array($crOrDr, ['CR', 'DR'])) {
            throw new \Exception("Invalid Cr/Dr value for ledger: $ledgerCode");
        }

        /* ===============================
           ✅ BASIC DETAILS
        =============================== */

        $ledger->ledgerName = $row['ledgername'];
        $ledger->ledgerCode = $ledgerCode;
        $ledger->groupId = $row['groupid'];
        $ledger->billBybill = $billByBill;
        $ledger->creditPeriod = $row['creditperiod'] ?? 0;
        $ledger->creditLimit = $row['creditlimit'] ?? 0;
        $ledger->ledgerType = $row['ledgertype'] ?? null;
        $ledger->routeId = $row['routeid'] ?? null;
        $ledger->currencyId = $row['currencyid'] ?? null;
        $ledger->pricingLevelId = $row['pricinglevelid'] ?? null;
        $ledger->address = $row['address'] ?? null;
        $ledger->phoneNo = $row['phoneno'] ?? null;
        $ledger->email = $row['email'] ?? null;
        $ledger->narration = $row['narration'] ?? null;

        /* ===============================
           ✅ BRANCH JSON
        =============================== */

        $branches = explode(',', str_replace('"', '', $row['branchids']));
        $branchJson = [];

        foreach ($branches as $branch) {
            $branchJson[] = [
                'branchId' => (int) trim($branch),
                'openingBalance' => $row['openingbalance'] ?? 0,
                'crOrDr' => $crOrDr
            ];
        }

        $ledger->branchId = $branchJson;
        $ledger->save();

        /* ===============================
           ✅ SAFE OPENING BALANCE INSERT
        =============================== */

        if (!empty($row['openingbalance'])) {

            $alreadyPosted = LedgerPosting::where('ledgerId', $ledger->ledgerId)
                ->where('voucherType', 'Opening Balance')
                ->exists();

            if (!$alreadyPosted) {

                foreach ($branchJson as $branch) {

                    $debit = $branch['crOrDr'] == 'DR'
                        ? $branch['openingBalance'] : 0;

                    $credit = $branch['crOrDr'] == 'CR'
                        ? $branch['openingBalance'] : 0;

                    LedgerPosting::create([
                        'date' => now(),
                        'voucherType' => 'Opening Balance',
                        'voucherNo' => $ledger->ledgerId,
                        'ledgerId' => $ledger->ledgerId,
                        'debit' => $debit,
                        'credit' => $credit,
                        'PostingType' => 'Opening',
                        'branchId' => $branch['branchId'],
                        'CreatedDate' => now(),
                        'CreatedUser' => 'BulkUpload'
                    ]);

                    if ($ledger->billBybill == 1) {
                        Partybalance::create([
                            'date' => now(),
                            'ledgerId' => $ledger->ledgerId,
                            'voucherType' => 'Opening Balance',
                            'voucherNo' => $ledger->ledgerId,
                            'debit' => $debit,
                            'credit' => $credit,
                            'BillAmount' => $branch['openingBalance'],
                            'branchId' => $branch['branchId'],
                            'CreatedDate' => now(),
                            'CreatedUser' => 'BulkUpload'
                        ]);
                    }
                }

                AccountGroup::where('groupId', $ledger->groupId)
                    ->increment('LedgerNextNo');
            }
        }

        /* ===============================
           ✅ SHIPPING ADDRESS
        =============================== */

        if (!empty($row['shipping_address1'])) {

            ShippingAddress::create([
                'ledgerId' => $ledger->ledgerId,
                'address1' => $row['shipping_address1'],
                'address2' => $row['shipping_address2'] ?? null,
                'address3' => $row['shipping_address3'] ?? null,
                'address4' => $row['shipping_address4'] ?? null,
                'Isdefault' => $row['shipping_isdefault'] ?? 0,
                'branchId' => $row['shipping_branchid'] ?? null,
                'CreatedDate' => now(),
                'CreatedUser' => 'BulkUpload'
            ]);
        }

        /* ===============================
           ✅ DOCUMENTS
        =============================== */

        if (!empty($row['document_name'])) {

            LedgerDocuments::create([
                'ledgerId' => $ledger->ledgerId,
                'document_name' => $row['document_name'],
                'file' => 'documents/' . ($row['filename'] ?? ''),
                'branchId' => $row['document_branchid'] ?? null,
                'createdDate' => now(),
                'createdUser' => 'BulkUpload'
            ]);
        }
    }

    public function rules(): array
    {
        return [
            '*.ledgername' => 'required',
            '*.ledgercode' => 'required',
            '*.groupid' => 'required|exists:tbl_AccountGroup,groupId',
            '*.billbybill' => 'required',
            '*.crordr' => 'required',
            '*.openingbalance' => 'nullable|numeric',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}