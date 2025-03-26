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
        Schema::create('carton_box_items', function (Blueprint $table) {
            $table->uuid('carton_box_id');
            $table->uuid('item_id');
            $table->boolean('is_validated')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->integer('validated_by')->nullable();
            $table->timestamps();
            $table->foreign('carton_box_id')->references('id')->on('carton_boxes')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->primary(['carton_box_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carton_box_items');
    }
};
