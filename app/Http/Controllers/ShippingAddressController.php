<?php

namespace App\Http\Controllers;

use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingAddressController extends Controller
{
    /**
	 * List ShippingAddress
	 */
	public function index()
{
    try {
        // Call PostgreSQL function to get all shipping addresses
        $shippingAddresses = DB::select('SELECT * FROM public.get_all_shipping_address()');

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Shipping Addresses retrieved successfully',
            'data'    => $shippingAddresses,
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
    }
}


	/**
	 * Get currency by id
	 */
	public function show($id)
{
    try {
        // Call PostgreSQL function to get shipping address by ID
        $ShippingAddress = DB::select(
            'SELECT * FROM public.get_shipping_address_byId(?)',
            [$id]
        );

        if (empty($ShippingAddress)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Shipping Address Not Found',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Shipping Address Retrieved Successfully',
            'data'    => $ShippingAddress[0],  // Return single record
        ]);
        
    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
    }
}


	/**
	 * Create ShippingAddress
	 */
	public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $ledgerId    = $request->ledgerId ?? null;
        $address1    = $request->address1 ?? null;
        $address2    = $request->address2 ?? null;
        $address3    = $request->address3 ?? null;
        $address4    = $request->address4 ?? null;
        $isDefault   = $request->Isdefault ?? false;
        $branchId    = $request->branchId ?? null;
        $createdUser = $request->CreatedUser ?? 'system';
        $createdDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');

        /* ---------- HANDLE DEFAULT ---------- */
        if ($isDefault && $ledgerId) {
            // Set all other addresses for the same ledgerId to false
            DB::update(
                'UPDATE public."tbl_ShippingAddress"
                 SET "Isdefault" = false
                 WHERE "ledgerId" = ?',
                [$ledgerId]
            );
        }

        /* ---------- CALL INSERT FUNCTION ---------- */
        $addressId = DB::select(
            'SELECT public.insert_shipping_address(?,?,?,?,?,?,?,?,?) AS "addressId"',
            [
                $ledgerId,
                $address1,
                $address2,
                $address3,
                $address4,
                $isDefault,
                $branchId,
                $createdUser,
                $createdDate
            ]
        );

        DB::commit();

        return response()->json([
            'status'  => 201,
            'error'   => false,
            'message' => 'Shipping Address Created Successfully',
            'data'    => $addressId[0]->addressId
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


	/**
	 * Update ShippingAddress
	 */
	public function update(Request $request, $id)
{
    DB::beginTransaction();

    try {
        // First, get existing address to check if it exists
        $existing = DB::select('SELECT * FROM public.get_shipping_address_byid(?)', [$id]);

        if (empty($existing)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Shipping Address Not Found',
            ], 404);
        }

        $ledgerId      = $request->ledgerId ?? $existing[0]->ledgerId;
        $address1      = $request->address1 ?? $existing[0]->address1;
        $address2      = $request->address2 ?? $existing[0]->address2;
        $address3      = $request->address3 ?? $existing[0]->address3;
        $address4      = $request->address4 ?? $existing[0]->address4;
        $isDefault     = $request->Isdefault ?? $existing[0]->Isdefault;
        $branchId      = $request->branchId ?? $existing[0]->branchId;
        $modifiedUser  = $request->ModifiedUser ?? $existing[0]->ModifiedUser;
        $modifiedDate  = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');

        // Call the PostgreSQL update function
        $result = DB::select(
            'SELECT public.update_shipping_address(?,?,?,?,?,?,?,?,?,?) AS "addressId"',
            [
                $id,
                $ledgerId,
                $address1,
                $address2,
                $address3,
                $address4,
                $isDefault,
                $branchId,
                $modifiedUser,
                $modifiedDate
            ]
        );

        if (empty($result) || $result[0]->addressId === null) {
            DB::rollBack();
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Shipping Address not found or not updated',
            ], 404);
        }

        DB::commit();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Shipping Address Updated Successfully',
            'data'    => $result[0]->addressId
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
    }
}

	/**
	 * Delete ShippingAddress
	 */
	public function destroy($id)
{
    DB::beginTransaction();

    try {
        // Call the PostgreSQL function to delete the address
        $result = DB::select(
            'SELECT public.delete_shipping_address(?) AS "addressId"',
            [$id]
        );

        if (empty($result) || $result[0]->addressId === null) {
            DB::rollBack();
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Shipping Address Not Found',
            ], 404);
        }

        DB::commit();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Shipping Address Deleted Successfully',
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
    }
}
}
