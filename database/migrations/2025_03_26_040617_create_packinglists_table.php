<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packing_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('buyer_id')->nullable();
            $table->string('purchase_order_number')->unique();
            $table->integer('carton_boxes_quantity');
            $table->json('details')->nullable();
            $table->string('carton_validation_rule')->default('SOLID');
            $table->timestamps();
            $table->foreign('buyer_id')->references('id')->on('buyers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packinglists');
    }
};
