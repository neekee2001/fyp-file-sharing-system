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
        Schema::create('share_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_file_id')->constrained('files');
            $table->foreignId('requested_by_user_id')->constrained('users');   
            $table->foreignId('requested_permission_id')->constrained('permissions');
            $table->unique(['requested_file_id', 'requested_by_user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_requests');
    }
};
