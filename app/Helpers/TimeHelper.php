<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

if (!function_exists('getBranchNow')) {

    function getBranchNow($branchId)
    {
        $timezone = DB::table('tbl_Branch')
            ->where('branchId', $branchId)
            ->value('timezone');

        if (!$timezone) {
            $timezone = config('app.timezone');
        }

        return Carbon::now($timezone)->format('Y-m-d H:i:s');
    }
}