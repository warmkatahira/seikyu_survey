<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-answer questions, each a reference to `choice_options`. Multi-select answers live in
     * `survey_response_choices` instead.
     *
     * @var list<string>
     */
    private const CHOICE_COLUMNS = [
        'invoice_composition_option_id',
        'customer_check_option_id',
        'detail_mailing_option_id',
        'dependency_option_id',
        'digitization_request_option_id',
        'detail_record_timing_option_id',
    ];

    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('billing_category', 50)->nullable();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();

            foreach (self::CHOICE_COLUMNS as $column) {
                $table->foreignId($column)->nullable()->constrained('choice_options')->restrictOnDelete();
            }

            $table->string('detail_record_timing_other', 100)->nullable();
            $table->unsignedSmallInteger('creation_minutes')->nullable();
            $table->unsignedSmallInteger('detail_creation_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
