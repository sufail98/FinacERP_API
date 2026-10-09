<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::create('tbl_AccountGroup', function (Blueprint $table) {
        //     $table->bigIncrements('groupId');
        //     $table->string('AccountGroupCode', 255)->nullable();
        //     $table->string('accountGroupName', 255)->nullable();
        //     $table->string('groupUnder', 255)->nullable();
        //     $table->text('narration')->nullable();
        //     $table->integer('LedgerNextNo')->nullable();
        //     $table->string('branchId', 100)->nullable();
        //     $table->timestamp('CreatedDate')->nullable();
        //     $table->string('CreatedUser', 100)->nullable();
        //     $table->timestamp('ModifiedDate')->nullable();
        //     $table->string('ModifiedUser', 100)->nullable();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_AccountGroup');
    }
};
