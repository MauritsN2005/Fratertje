<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
        if (DB::table('members')->exists() || DB::table('member_types')->exists()) {
            throw new RuntimeException('Maak eerst een back-up. Deze uitbreiding vereist lege domeintabellen; zie README voor de migratie van bestaande gegevens.');
        }
        Schema::table('users', fn (Blueprint $t) => $t->rememberToken());
        Schema::table('member_types', function (Blueprint $t) {
            $t->string('code', 10)->unique();
            $t->softDeletes();
            $t->unique(['id', 'code']);
        });
        Schema::table('addresses', fn (Blueprint $t) => $t->softDeletes());
        Schema::table('members', function (Blueprint $t) {
            $t->renameColumn('is_activate', 'is_active');
            $t->string('email')->unique();
            $t->string('membership_code', 10);
            $t->string('nbvv_number', 20)->nullable()->unique();
            $t->date('registered_at');
            $t->date('starts_at');
            $t->date('ends_at')->nullable();
            $t->timestamp('consented_at')->nullable();
            $t->string('consent_version')->nullable();
            $t->unique(['id', 'nbvv_number']);
            $t->foreign(['member_type_id', 'membership_code'])->references(['id', 'code'])->on('member_types');
        });
        Schema::table('breeding_numbers', function (Blueprint $t) {
            $t->softDeletes();
            $t->foreign(['member_id', 'breeding_number'])->references(['id', 'nbvv_number'])->on('members')->cascadeOnUpdate();
        });
        Schema::create('annual_rates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_type_id')->constrained();
            $t->unsignedSmallInteger('year');
            $t->unsignedInteger('amount_cents');
            $t->timestamp('announced_at');
            $t->timestamps();
            $t->unique(['member_type_id', 'year']);
        });
        Schema::create('membership_requests', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 20);
            $t->string('status', 20)->default('pending');
            $t->json('payload');
            $t->foreignId('member_id')->nullable()->constrained();
            $t->date('requested_at');
            $t->date('effective_at');
            $t->timestamp('consented_at');
            $t->string('consent_version');
            $t->foreignId('processed_by')->nullable()->constrained('users');
            $t->timestamp('processed_at')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('member_id')->constrained();
            $t->unsignedSmallInteger('year');
            $t->string('kind', 10);
            $t->unsignedInteger('annual_cents');
            $t->unsignedTinyInteger('months');
            $t->integer('amount_cents');
            $t->json('snapshot');
            $t->timestamps();
            $t->unique(['member_id', 'year', 'kind']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE member_types ADD CONSTRAINT types_code_check CHECK (code IN ('adult','youth','guest'))");
            DB::statement("ALTER TABLE members ADD CONSTRAINT nbvv_required_check CHECK ((membership_code = 'guest' AND nbvv_number IS NULL) OR (membership_code IN ('adult','youth') AND nbvv_number IS NOT NULL AND CHAR_LENGTH(nbvv_number) > 0))");
            DB::statement('ALTER TABLE members ADD CONSTRAINT membership_dates_check CHECK (ends_at IS NULL OR ends_at >= starts_at)');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Deze migratie bevat administratiehistorie. Herstel een back-up in plaats van gegevens te verwijderen.');
    }
};
