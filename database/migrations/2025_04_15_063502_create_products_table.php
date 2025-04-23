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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('added_by', ['admin', 'seller'])->default('admin');

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('cascade');

            $table->text('thumbnail')->nullable();
            $table->json('photos')->nullable();
            $table->string('sku')->nullable();
            $table->json('tags')->nullable();
            $table->json('variants')->nullable();

            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            $table->decimal('unit_price', 10, 2);
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('discount', 10, 2)->nullable();
            $table->enum('discount_type', ['percent', 'amount'])->nullable();
            $table->timestamp('discount_start_date')->nullable();
            $table->timestamp('discount_end_date')->nullable();

            $table->enum('published', ['pending', 'published'])->default('pending');
            $table->enum('approved', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('reason_reject')->nullable(); // fixed typo

            $table->boolean('featured')->default(false);
            $table->enum('stock_visibility_state', ['quantity', 'text', 'hide'])->nullable();
            $table->integer('current_stock')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->integer('min_qty')->nullable();
            $table->integer('low_stock_quantity')->nullable();
            $table->decimal('tax', 8, 2)->nullable();
            $table->enum('tax_type', ['percent', 'amount'])->nullable();

            $table->enum('shipping_type', ['free', 'flat_rate', 'product_wise'])->nullable();
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->boolean('is_quantity_multiplied')->default(false);
            $table->string('est_shipping_days')->nullable();

            $table->integer('number_of_sales')->default(0); // fixed typo
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_img')->nullable();

            $table->boolean('refundable')->default(false);
            $table->decimal('rating', 3, 2)->nullable(); // adjust if needed
            $table->integer('views')->default(0);

            $table->timestamps();
        });


        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('locale')->index();

            // Translatable fields
            $table->string('name')->nullable();
            $table->string('unit')->nullable();
            $table->string('reason_reject')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('tags')->nullable();

            $table->unique(['product_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
