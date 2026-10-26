<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeding_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->unique()->constrained('members');
            $table->string('breeding_number')->unique();
            $table->year('issue_year');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_numbers');
    }
};
