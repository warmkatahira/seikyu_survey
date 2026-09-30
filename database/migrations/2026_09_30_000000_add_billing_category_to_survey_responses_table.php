<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One customer may be invoiced separately per business line (e.g. 卸 and 通販), giving one
 * answer per invoice; this free-text label tells those answers apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->string('billing_category', 50)->nullable()->after('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropColumn('billing_category');
        });
    }
};
