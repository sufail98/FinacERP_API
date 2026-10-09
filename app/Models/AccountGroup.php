<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountGroup extends Model
{
    protected $table = 'tbl_AccountGroup';
	protected $primaryKey = 'groupId';
	public $timestamps = false;

	protected $fillable = [
		'groupId',
		'AccountGroupCode',
		'accountGroupName',
		'groupUnder',
		'narration',
		'LedgerNextNo',
		'branchId',
		'default',
		'CreatedDate',
		'CreatedUser',
		'ModifiedDate',
		'ModifiedUser',
	];


	// Relationship to parent group
    public function parentGroup()
    {
        return $this->belongsTo(AccountGroup::class, 'groupUnder', 'groupId');
    }

	/**
     * Recursive query with fixed groupIds.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getUnderGroups()
    {
        // 🔹 Fixed groupIds here
        $ids = [28, 29];
        $idsString = implode(',', $ids);

        $sql = <<<SQL
            WITH RECURSIVE GroupInMainGroupP AS (
                SELECT "groupId", 1 AS "HierarchyLevel"
                FROM "tbl_AccountGroup"
                WHERE "groupId" IN ($idsString)

                UNION ALL

                SELECT e."groupId", g."HierarchyLevel" + 1
                FROM "tbl_AccountGroup" e
                INNER JOIN GroupInMainGroupP g ON e."groupUnder" = g."groupId"
            )
            SELECT A."groupId", A."accountGroupName", A."groupUnder"
            FROM "tbl_AccountGroup" A
            WHERE A."groupId" IN (SELECT DISTINCT "groupId" FROM GroupInMainGroupP)
            ORDER BY A."accountGroupName" ASC
        SQL;

        return DB::select($sql);
    }
}
