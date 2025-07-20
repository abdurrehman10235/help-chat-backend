<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{

     Schema::create('service_categories', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique(); // e.g. pre-arrival, arrival, etc.
    $table->string('name_en');
    $table->string('name_ar');
    $table->timestamps();
});
    Schema::create('services_en', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained('service_categories')->onDelete('cascade');
    $table->string('slug')->unique();
    $table->string('name');
    $table->text('description');
    $table->string('image_url')->nullable();
    $table->decimal('price', 8, 2)->nullable();
    $table->timestamps();
});

Schema::create('services_ar', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained('service_categories')->onDelete('cascade');
    $table->string('slug')->unique();
    $table->string('name');
    $table->text('description');
    $table->string('image_url')->nullable();
    $table->decimal('price', 8, 2)->nullable();
    $table->timestamps();
});

   
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services_ar');
        Schema::dropIfExists('services_en');
        Schema::dropIfExists('service_categories');
    }
};
