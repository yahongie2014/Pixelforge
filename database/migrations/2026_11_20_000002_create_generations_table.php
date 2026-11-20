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
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pixelforge_id', 100)->nullable()->index();
            $table->string('idempotency_key', 100)->unique();
            $table->enum('status', ['queued', 'processing', 'succeeded', 'failed'])->default('queued')->index();
            $table->text('prompt');
            $table->string('size', 20)->default('1024x1024');
            $table->string('quality', 20)->default('medium');
            $table->json('reference_images')->nullable();
            $table->json('metadata')->nullable();

            $table->text('image_url')->nullable();
            $table->timestamp('image_url_expires_at')->nullable();

            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();

            // Usage counts
            $table->unsignedInteger('input_text_tokens')->default(0);
            $table->unsignedInteger('input_image_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('reasoning_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);

            // Rates snapshot in force at creation
            $table->json('rates')->nullable();

            // Accurate financial figures in USD (9 decimal places for exact cent/sub-cent precision)
            $table->decimal('estimated_cost_provider', 14, 9)->default('0.000000000');
            $table->decimal('estimated_cost_user', 14, 9)->default('0.000000000');
            $table->decimal('actual_cost_provider', 14, 9)->default('0.000000000');
            $table->decimal('actual_cost_user', 14, 9)->default('0.000000000');

            // Pixelforge lifecycle timestamps
            $table->timestamp('pixelforge_created_at')->nullable();
            $table->timestamp('pixelforge_started_at')->nullable();
            $table->timestamp('pixelforge_completed_at')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
