<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carton_boxes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('barcode');
            $table->string('internal_sku');
            $table->string('validation_status')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->integer('processed_by')->nullable();
            $table->integer('items_quantity');
            $table->uuid('packing_list_id')->nullable();
            $table->timestamps();
            $table->foreign('packing_list_id')->references('id')->on('packing_lists');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carton_boxes');
    }
};
