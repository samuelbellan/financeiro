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
        Schema::create('fiscal_concursos', function (Blueprint $table) {
            $table->id();
            $table->string('sigla', 50)->index(); // Ex: RFB, SEFAZ-SP, ISS-SP
            $table->string('nome_orgao', 150); // Ex: Secretaria da Fazenda do Estado de São Paulo
            $table->enum('esfera', ['federal', 'estadual', 'municipal'])->index();
            $table->string('uf', 2)->nullable()->index(); // SP, RJ, MG, etc.
            $table->string('municipio', 100)->nullable()->index(); // São Paulo, Rio de Janeiro, etc.
            
            // Cargos e Status
            $table->string('cargo_principal', 150); // Auditor Fiscal da Receita Estadual
            $table->json('cargos_secundarios')->nullable(); // Analista Tributário, Julgador, etc.
            $table->enum('status', [
                'previsto',
                'solicitado',
                'autorizado',
                'comissao_formada',
                'escolha_banca',
                'banca_definida',
                'edital_publicado',
                'inscricoes_abertas',
                'em_andamento',
                'concluido'
            ])->default('previsto')->index();
            
            $table->string('banca', 100)->nullable(); // FGV, Cebraspe, FCC, Vunesp, etc.
            $table->string('vagas_previstas', 100)->nullable(); // 150 + CR
            $table->string('requisito_escolaridade', 255)->default('Nível Superior em qualquer área');
            $table->string('jornada', 50)->default('40h semanais');
            
            // Remuneração Detalhada (Pesquisa Aprofundada)
            $table->decimal('remuneracao_inicial_bruta', 12, 2)->default(0); // Inicial real (Base + Gratificação)
            $table->decimal('vencimento_basico', 12, 2)->default(0); // Vencimento Base
            $table->decimal('produtividade_estimada', 12, 2)->default(0); // Parcela variável/produtividade
            $table->text('produtividade_detalhes')->nullable(); // Ex: PR (Prêmio de Produtividade), Quotas, PDF, Bônus de Eficiência
            $table->decimal('beneficios_estimados', 12, 2)->default(0); // Alimentação + Saúde + Transporte + Fronteira
            $table->text('beneficios_detalhes')->nullable();
            $table->decimal('remuneracao_real_transparencia', 12, 2)->default(0); // Média constatada no portal da transparência
            $table->decimal('remuneracao_teto', 12, 2)->default(0); // Remuneração máxima no teto constitucional
            $table->json('tabela_rubricas')->nullable(); // Array detalhado com todas as verbas
            $table->json('evolucao_carreira')->nullable(); // Níveis/Classes: Inicial, Intermediário, Final
            
            // Informações Complementares
            $table->string('lei_carreira', 255)->nullable(); // Ex: Lei Complementar nº 1.059/2008
            $table->json('disciplinas_chave')->nullable(); // Ex: Direito Tributário, Contabilidade Geral e Avançada, Legislação Tributária, TI
            $table->integer('ultimo_concurso_ano')->nullable();
            $table->string('ultimo_concurso_banca', 100)->nullable();
            $table->string('ultimo_concurso_vagas', 100)->nullable();
            $table->string('ultimo_concurso_link', 500)->nullable();
            $table->string('link_portal_transparencia', 500)->nullable();
            $table->text('observacoes_estrategicas')->nullable(); // Dicas e insights para o concurseiro
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_concursos');
    }
};
