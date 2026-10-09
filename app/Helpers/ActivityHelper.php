<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class ActivityHelper
{
    /**
     * Log activity using PostgreSQL log_user_activity function
     */
    public static function logActivity(
        $userId,                  // can accept string/int from request
        string $activityType,
        string $voucherType,
        string $action,
        $branchId,                // can accept string/int from request
        ?string $createdUser = null,
        ?int $masterId = null,
        ?string $voucherNo = null,
        $voucherDate = null,      // can accept string or Carbon instance
        $voucherOldAmount = null,
        $voucherNewAmount = null,
        ?string $systemId = null
    ) {
        // Ensure integers
        $userId = (int)$userId;
        $branchId = (int)$branchId;

        // Default createdUser
        $createdUser = $createdUser ?? 'System';

        // Automatically get server IP as systemName
        $systemName = getHostByName(getHostName());

        // Convert voucherDate to proper timestamp format
        if ($voucherDate) {
            $voucherDate = date('Y-m-d H:i:s', strtotime($voucherDate));
        }

        $result = DB::select(
            'SELECT log_user_activity(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? ,?) AS id',
            [
                $userId,
                $systemName,
                $activityType,
                $voucherType,
                $action,
                $branchId,
                $createdUser,
                $masterId,
                $voucherNo,
                $voucherDate,
                $voucherOldAmount,
                $voucherNewAmount,
                $systemId
            ]
        );

        return $result[0]->id ?? null;
    }
}