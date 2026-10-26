<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_type_id')->constrained('member_types');
            $table->foreignId('address_id')->unique()->constrained('addresses');
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->boolean('is_activate');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
