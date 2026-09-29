<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns that reference `choice_options`, in display order on the answer form.
     *
     * @var list<string>
     */
    private const CHOICE_COLUMNS = [
        'storage_fee_option_id',
        'handling_fee_option_id',
        'freight_fee_option_id',
        'other_work_option_id',
        'storage_billing_method_option_id',
        'closing_day_option_id',
        'detail_presence_option_id',
        'detail_format_option_id',
        'data_source_primary_option_id',
        'data_source_secondary_option_id',
        'record_timing_option_id',
        'price_basis_option_id',
        'copy_previous_month_option_id',
        'irregular_frequency_option_id',
        'dependency_option_id',
    ];

    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();

            foreach (self::CHOICE_COLUMNS as $column) {
                $table->foreignId($column)->nullable()->constrained('choice_options')->restrictOnDelete();
            }

            $table->unsignedSmallInteger('creation_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
