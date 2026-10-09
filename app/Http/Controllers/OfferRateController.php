<?php

namespace App\Http\Controllers;

use App\Models\OfferRate;
use App\Models\OfferRateDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

            $offers = OfferRate::with('details')->get();

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $offers
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $offerId = (OfferRate::max('offerId') ?? 0) + 1;


            $offer = OfferRate::create([
                'offerId'      => $offerId,
                'offerCode'    => $request->offerCode,
                'offerName'    => $request->offerName,
                'fromDate'     => $request->fromDate,
                'toDate'       => $request->toDate,
                'pricingLevel' => $request->pricingLevel,
                'IsApproved'   => $request->IsApproved ?? false,
                'IsActive'     => $request->IsActive ?? true,
                'branchId'     => $request->branchId,
                'CreatedDate'  => now()->setTimezone('Asia/Kolkata'),
                'CreatedUser'  => $request->CreatedUser,
                'ModifiedDate' => null,
                'ModifiedUser' => null,
            ]);


            if (!empty($request->offerDetails)) {
                foreach ($request->offerDetails as $od) {
                    $offerDetailsId = (OfferRateDetails::max('offerDetailsId') ?? 0) + 1;

                    OfferRateDetails::create([
                        'offerDetailsId'   => $offerDetailsId,
                        'offerId'          => $offer->offerId,
                        'lineIndex'        => $od['lineIndex'],
                        'productCode'      => $od['productCode'],
                        'barcode'          => $od['barcode'],
                        'unitId'           => $od['unitId'],
                        'fromDate'         => $od['fromDate'],
                        'toDate'           => $od['toDate'],
                        'pricingLevelId'   => $od['pricingLevelId'],
                        'IsApproved'       => $od['IsApproved'] ?? false,
                        'IsActive'         => $od['IsActive'] ?? true,
                        'AmountBeforeDisc' => $od['AmountBeforeDisc'],
                        'DiscPer'          => $od['DiscPer'],
                        'DiscAmount'       => $od['DiscAmount'],
                        'salesPrice'       => $od['salesPrice'],
                        'branchId'         => $request->branchId,
                        'CreatedDate'      => now()->setTimezone('Asia/Kolkata'),
                        'CreatedUser'      => $request->CreatedUser,
                        'ModifiedDate'     => null,
                        'ModifiedUser'     => null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Offer & details saved successfully',
                'offerId' => $offer->offerId
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {

            $offer = OfferRate::with('details')->find($id);

            if (!$offer) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Offer not found',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $offer
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            $offer = OfferRate::with('details')->find($id);
            if (!$offer) {
                DB::rollBack();
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Offer not found'
                ], 404);
            }
            $offer->update([
                'offerCode'    => $request->offerCode,
                'offerName'    => $request->offerName,
                'fromDate'     => $request->fromDate,
                'toDate'       => $request->toDate,
                'pricingLevel' => $request->pricingLevel,
                'IsApproved'   => $request->IsApproved ?? $offer->IsApproved,
                'IsActive'     => $request->IsActive ?? $offer->IsActive,
                'branchId'     => $request->branchId,
                'ModifiedDate' => now()->setTimezone('Asia/Kolkata'),
                'ModifiedUser' => $request->ModifiedUser,
            ]);
            OfferRateDetails::where('offerId', $offer->offerId)->delete();

            if (!empty($request->offerDetails)) {
                foreach ($request->offerDetails as $od) {

                    $offerDetailsId = (OfferRateDetails::max('offerDetailsId') ?? 0) + 1;

                    OfferRateDetails::create([
                        'offerDetailsId'   => $offerDetailsId,
                        'offerId'          => $offer->offerId,
                        'lineIndex'        => $od['lineIndex'],
                        'productCode'      => $od['productCode'],
                        'barcode'          => $od['barcode'],
                        'unitId'           => $od['unitId'],
                        'fromDate'         => $od['fromDate'],
                        'toDate'           => $od['toDate'],
                        'pricingLevelId'   => $od['pricingLevelId'],
                        'IsApproved'       => $od['IsApproved'] ?? false,
                        'IsActive'         => $od['IsActive'] ?? true,
                        'AmountBeforeDisc' => $od['AmountBeforeDisc'],
                        'DiscPer'          => $od['DiscPer'],
                        'DiscAmount'       => $od['DiscAmount'],
                        'salesPrice'       => $od['salesPrice'],
                        'branchId'         => $request->branchId,
                        'CreatedDate'      => $offer->CreatedDate,
                        'CreatedUser'      => $offer->CreatedUser,
                        'ModifiedDate'     => now()->setTimezone('Asia/Kolkata'),
                        'ModifiedUser'     => $request->ModifiedUser,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Offer & details updated successfully',
                'offerId' => $offer->offerId
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $offer = OfferRate::with('details')->find($id);
            if (!$offer) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Offer rate not found',
                ]);
            }
            if ($offer->details()->exists()) {
                $offer->details()->delete();
            }
            $offer->delete();
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Offer rate deleted successfully',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function filter(Request $request)
    {
        $p_maingroup = $request->input('maingroup', null);
        $p_group1    = $request->input('group1', null);
        $p_group2    = $request->input('group2', null);
        $p_group3    = $request->input('group3', null);
        $p_group4    = $request->input('group4', null);
        try {
            $products = DB::select(
                'SELECT * FROM public.get_products_by_group(:maingroup, :group1, :group2, :group3, :group4)',
                [
                    'maingroup' => $p_maingroup,
                    'group1'    => $p_group1,
                    'group2'    => $p_group2,
                    'group3'    => $p_group3,
                    'group4'    => $p_group4,
                ]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $products
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}
