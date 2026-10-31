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
        // 1. Tabela contas
        if (!Schema::hasTable('contas')) {
            Schema::create('contas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('nome', 150);
                $table->string('tipo', 30)->default('corrente'); // corrente, poupanca, cartao_credito
                $table->string('banco_id', 20)->nullable();
                $table->string('agencia', 20)->nullable();
                $table->string('numero_conta', 50)->nullable();
                $table->string('moeda', 10)->default('BRL');
                $table->decimal('saldo_atual', 15, 2)->default(0.00);
                $table->dateTime('data_ultimo_saldo')->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'banco_id', 'numero_conta']);
            });
        }

        // 2. Colunas adicionais em transacoes para suportar OFX e deduplicação por FITID
        if (Schema::hasTable('transacoes')) {
            Schema::table('transacoes', function (Blueprint $table) {
                if (!Schema::hasColumn('transacoes', 'conta_id')) {
                    $table->foreignId('conta_id')->nullable()->after('user_id')->constrained('contas')->nullOnDelete();
                }

                if (!Schema::hasColumn('transacoes', 'data_hora')) {
                    $table->dateTime('data_hora')->nullable()->after('data');
                }

                if (!Schema::hasColumn('transacoes', 'fitid')) {
                    $table->string('fitid', 255)->nullable()->after('data_hora');
                }

                if (!Schema::hasColumn('transacoes', 'numero_documento')) {
                    $table->string('numero_documento', 64)->nullable()->after('fitid');
                }

                if (!Schema::hasColumn('transacoes', 'tipo_transacao_ofx')) {
                    $table->string('tipo_transacao_ofx', 32)->nullable()->after('numero_documento');
                }

                if (!Schema::hasColumn('transacoes', 'memo')) {
                    $table->text('memo')->nullable()->after('tipo_transacao_ofx');
                }

                if (!Schema::hasColumn('transacoes', 'valor_original_ofx')) {
                    $table->decimal('valor_original_ofx', 15, 2)->nullable()->after('memo');
                }
            });

            // Adicionar índice UNIQUE para garantir integridade e deduplicação rígida
            try {
                Schema::table('transacoes', function (Blueprint $table) {
                    $table->unique(['conta_id', 'fitid'], 'uq_conta_fitid');
                });
            } catch (\Throwable) {
                // Índice pode já existir
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transacoes')) {
            Schema::table('transacoes', function (Blueprint $table) {
                try {
                    $table->dropUnique('uq_conta_fitid');
                } catch (\Throwable) {}

                $colsToDrop = [
                    'valor_original_ofx',
                    'memo',
                    'tipo_transacao_ofx',
                    'numero_documento',
                    'fitid',
                    'data_hora',
                    'conta_id',
                ];

                foreach ($colsToDrop as $col) {
                    if (Schema::hasColumn('transacoes', $col)) {
                        try {
                            $table->dropColumn($col);
                        } catch (\Throwable) {}
                    }
                }
            });
        }

        Schema::dropIfExists('contas');
    }
};
