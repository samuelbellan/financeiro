<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Atualiza tabela users
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('google_id');
            $table->boolean('is_admin')->default(false)->after('remember_token');
            $table->string('password')->nullable()->change();
        });

        // Promove o primeiro usuário existente a administrador
        $firstUser = DB::table('users')->orderBy('id')->first();
        if ($firstUser) {
            DB::table('users')->where('id', $firstUser->id)->update(['is_admin' => true]);
        }

        // 2. Tabela de e-mails autorizados para login Google
        Schema::create('allowed_google_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('nome')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Adiciona o e-mail do admin na whitelist inicial
        if ($firstUser && !empty($firstUser->email)) {
            DB::table('allowed_google_emails')->insertOrIgnore([
                'email' => strtolower($firstUser->email),
                'nome' => $firstUser->name ?? 'Administrador',
                'created_by' => $firstUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('allowed_google_emails');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar', 'is_admin']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
