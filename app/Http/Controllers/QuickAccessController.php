<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuickAccess;
use Illuminate\Support\Facades\DB;

class QuickAccessController extends Controller
{
    public function index($branchId = null)
    {
        try {

            $result = DB::select(
                'SELECT * FROM public.get_all_quick_access(?)',
                [$branchId]
            );
            $result = collect($result)->map(function ($item) {
                $item->UserGroups = $item->UserGroups
                    ? json_decode($item->UserGroups, true)
                    : [];
                return $item;
            });
            return response()->json([
                'status' => true,
                'data' => $result
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $id = null)
    {
        DB::beginTransaction();
        try {
            // Normalize input: support both a single object and an array of objects
            $items = $request->all();

            // If the payload is a single object (has WindowName/Status keys directly),
            // wrap it in an array so we can process it the same way
            if (isset($items['WindowName']) || isset($items['Status'])) {
                $items = [array_merge(['id' => $id], $items)];
            }

            if (empty($items) || !is_array($items)) {
                DB::rollBack();
                return response()->json([
                    'status' => 422,
                    'error' => true,
                    'message' => 'Invalid payload. Expecting an object or array of objects.',
                ], 422);
            }

            $updatedIds = [];
            $notFoundIds = [];

            foreach ($items as $item) {
                $itemId = $item['id'] ?? $id;

                $quickAccess = QuickAccess::find($itemId);

                if (!$quickAccess) {
                    $notFoundIds[] = $itemId;
                    continue;
                }

                DB::select(
                    'SELECT * FROM public.update_quick_access(?, ?, ?, ?, ?, ?)',
                    [
                        $itemId,
                        $item['WindowName'] ?? null,
                        $item['Status'] ?? null,
                        $item['branchId'] ?? null,
                        isset($item['usergroupIds'])
                            ? json_encode($item['usergroupIds'])
                            : json_encode([]),
                        $item['ModifiedUser'] ?? null,
                    ]
                );

                $updatedIds[] = $itemId;
            }

            if (!empty($notFoundIds)) {
                DB::rollBack();
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'QuickAccess id(s) not found',
                    'notFound' => $notFoundIds,
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'QuickAccess updated successfully',
                'updatedIds' => $updatedIds,
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
}
