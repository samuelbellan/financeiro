<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExerciseCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exercises = [
            // ─── MUSCULAÇÃO: PEITORAL ─────────────────────────
            [
                'nome' => 'Supino Reto com Barra',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'peito',
                'grupo_muscular_secundario' => ['triceps', 'deltoide_anterior'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'barra',
                'instrucoes_execucao' => 'Deite no banco, mantenha as escápulas retraídas, desça a barra controladamente até o terço inferior do peito e empurre até a extensão quase completa dos cotovelos.',
            ],
            [
                'nome' => 'Supino Inclinado com Halteres',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'peito',
                'grupo_muscular_secundario' => ['deltoide_anterior', 'triceps'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'halteres',
                'instrucoes_execucao' => 'Banco ajustado a 30-45 graus. Mantenha os cotovelos a 45-60 graus do tronco.',
            ],
            [
                'nome' => 'Crossover na Polia Média/Alta',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'peito',
                'grupo_muscular_secundario' => ['deltoide_anterior'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'polia',
                'instrucoes_execucao' => 'Mantenha leve flexão nos cotovelos e contraia o peitoral no ponto central mantendo 1 segundo de pico de contração.',
            ],
            [
                'nome' => 'Flexão de Braço (Push-up)',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'peito',
                'grupo_muscular_secundario' => ['triceps', 'deltoide_anterior', 'abdomen'],
                'tipo_medicao' => 'reps_apenas',
                'equipamento' => 'peso_corporal',
                'instrucoes_execucao' => 'Corpo alinhado em prancha, cotovelos em flecha, descer até o peito quase encostar no chão.',
            ],

            // ─── MUSCULAÇÃO: COSTAS ───────────────────────────
            [
                'nome' => 'Puxada Alta Frontal',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'costas',
                'grupo_muscular_secundario' => ['biceps', 'deltoide_posterior'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'polia',
                'instrucoes_execucao' => 'Puxe a barra em direção à parte superior do tórax deprimindo e retraindo as escápulas.',
            ],
            [
                'nome' => 'Remada Curvada com Barra',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'costas',
                'grupo_muscular_secundario' => ['biceps', 'lombar', 'deltoide_posterior'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'barra',
                'instrucoes_execucao' => 'Tronco inclinado a 45 graus, coluna neutra, puxe a barra rente às coxas em direção ao umbigo.',
            ],
            [
                'nome' => 'Remada Baixa no Triângulo',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'costas',
                'grupo_muscular_secundario' => ['biceps', 'trapezio'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'polia',
                'instrucoes_execucao' => 'Puxe o triângulo contra o abdome projetando o peito para a frente e aproximando as escápulas.',
            ],
            [
                'nome' => 'Levantamento Terra Convencional',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'costas',
                'grupo_muscular_secundario' => ['gluteos', 'isquiotibiais', 'quadriceps', 'lombar', 'trapezio'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'barra',
                'instrucoes_execucao' => 'Pés na largura do quadril, coluna neutra, pegada firme, empurre o chão com os calcanhares mantendo a barra colada nas canelas.',
            ],
            [
                'nome' => 'Barra Fixa (Pull-up)',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'costas',
                'grupo_muscular_secundario' => ['biceps', 'deltoide_posterior'],
                'tipo_medicao' => 'reps_apenas',
                'equipamento' => 'peso_corporal',
                'instrucoes_execucao' => 'Pegada pronada aberta, puxe o queixo acima da barra controlando a fase excêntrica.',
            ],

            // ─── MUSCULAÇÃO: OMBROS & TRAPÉZIO ────────────────
            [
                'nome' => 'Desenvolvimento com Halteres Sentado',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'ombros',
                'grupo_muscular_secundario' => ['triceps', 'deltoide_anterior'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'halteres',
                'instrucoes_execucao' => 'Empurre os halteres verticalmente acima da cabeça sem bater os pesos no topo.',
            ],
            [
                'nome' => 'Elevação Lateral com Halteres',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'ombros',
                'grupo_muscular_secundario' => ['trapezio'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'halteres',
                'instrucoes_execucao' => 'Eleve os braços lateralmente até a linha dos ombros mantendo o cotovelo levemente destravado.',
            ],
            [
                'nome' => 'Crucifixo Invertido na Polia / Máquina',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'ombros',
                'grupo_muscular_secundario' => ['deltoide_posterior', 'trapezio'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'polia',
                'instrucoes_execucao' => 'Foco no deltóide posterior com movimento horizontal de abertura dos braços.',
            ],

            // ─── MUSCULAÇÃO: BRAÇOS (BÍCEPS & TRÍCEPS) ─────────
            [
                'nome' => 'Rosca Direta com Barra W',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'biceps',
                'grupo_muscular_secundario' => ['antebraço'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'barra',
                'instrucoes_execucao' => 'Mantenha os cotovelos fixos ao lado do corpo, flexionando os antebraços sem usar impulso do tronco.',
            ],
            [
                'nome' => 'Rosca Martelo com Halteres',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'biceps',
                'grupo_muscular_secundario' => ['braquial', 'braquiorradial'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'halteres',
                'instrucoes_execucao' => 'Pegada neutra (palmas voltadas para dentro), excelente para espessura de braço e antebraço.',
            ],
            [
                'nome' => 'Tríceps Polia com Corda',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'triceps',
                'grupo_muscular_secundario' => [],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'polia',
                'instrucoes_execucao' => 'Abra as pontas da corda na parte mais baixa do movimento para contração máxima da cabeça lateral do tríceps.',
            ],
            [
                'nome' => 'Tríceps Testa com Halteres ou Barra',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'triceps',
                'grupo_muscular_secundario' => [],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'halteres',
                'instrucoes_execucao' => 'Mantenha os braços levemente inclinados para trás em relação à vertical para tensão constante na cabeça longa.',
            ],

            // ─── MUSCULAÇÃO: MEMBROS INFERIORES ───────────────
            [
                'nome' => 'Agachamento Livre com Barra',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'quadriceps',
                'grupo_muscular_secundario' => ['gluteos', 'isquiotibiais', 'lombar', 'abdomen'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'barra',
                'instrucoes_execucao' => 'Pés afastados na largura dos ombros, desça flexionando quadris e joelhos até que as coxas fiquem pelo menos paralelas ao chão.',
            ],
            [
                'nome' => 'Leg Press 45°',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'quadriceps',
                'grupo_muscular_secundario' => ['gluteos'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'maquina',
                'instrucoes_execucao' => 'Desça a plataforma sem deixar a pelve retroverter (tirar a lombar do encosto). Não bloqueie os joelhos na extensão.',
            ],
            [
                'nome' => 'Cadeira Extensora',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'quadriceps',
                'grupo_muscular_secundario' => [],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'maquina',
                'instrucoes_execucao' => 'Extensão de pernas com 1 segundo de pausa no topo.',
            ],
            [
                'nome' => 'Stiff com Barra ou Halteres',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'isquiotibiais',
                'grupo_muscular_secundario' => ['gluteos', 'lombar'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'halteres',
                'instrucoes_execucao' => 'Joelhos semiflexionados, projete o quadril para trás mantendo a coluna alinhada e sinta o alongamento da cadeia posterior.',
            ],
            [
                'nome' => 'Mesa Flexora',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'isquiotibiais',
                'grupo_muscular_secundario' => ['panturrilha'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'maquina',
                'instrucoes_execucao' => 'Flexione as pernas aproximando os calcanhares dos glúteos mantendo a pelve colada no banco.',
            ],
            [
                'nome' => 'Elevação Pélvica com Barra',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'gluteos',
                'grupo_muscular_secundario' => ['isquiotibiais'],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'barra',
                'instrucoes_execucao' => 'Escápulas apoiadas no banco, eleve a pelve contraindo os glúteos firmemente no topo.',
            ],
            [
                'nome' => 'Panturrilha em Pé na Máquina',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'panturrilha',
                'grupo_muscular_secundario' => [],
                'tipo_medicao' => 'peso_e_reps',
                'equipamento' => 'maquina',
                'instrucoes_execucao' => 'Amplitude total: desça bem o calcanhar para alongar e suba até a ponta dos pés pausando no topo.',
            ],

            // ─── MUSCULAÇÃO: ABDÔMEN & CORE ────────────────────
            [
                'nome' => 'Prancha Abdominal Isométrica',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'abdomen',
                'grupo_muscular_secundario' => ['lombar', 'gluteos'],
                'tipo_medicao' => 'tempo_apenas',
                'equipamento' => 'peso_corporal',
                'instrucoes_execucao' => 'Apoio nos antebraços e pontas dos pés, mantendo coluna alinhada e glúteos contraídos.',
            ],
            [
                'nome' => 'Elevação de Pernas na Barra',
                'categoria' => 'musculacao',
                'grupo_muscular_primario' => 'abdomen',
                'grupo_muscular_secundario' => ['flexores_quadril'],
                'tipo_medicao' => 'reps_apenas',
                'equipamento' => 'peso_corporal',
                'instrucoes_execucao' => 'Suspenso na barra, eleve os joelhos ou pernas estendidas flexionando a pelve em direção ao tórax.',
            ],

            // ─── CORRIDA & CARDIO ─────────────────────────────
            [
                'nome' => 'Corrida Contínua / Rodagem (Easy Run)',
                'categoria' => 'corrida',
                'grupo_muscular_primario' => 'cardiovascular',
                'grupo_muscular_secundario' => ['quadriceps', 'panturrilha', 'isquiotibiais'],
                'tipo_medicao' => 'tempo_e_distancia',
                'equipamento' => 'livre',
                'instrucoes_execucao' => 'Corrida em ritmo confortável e conversacional (Zona 2). Foco em volume aeróbico e eficiência neuromuscular.',
            ],
            [
                'nome' => 'Treino de Tiros / Intervalado (HIIT)',
                'categoria' => 'corrida',
                'grupo_muscular_primario' => 'cardiovascular',
                'grupo_muscular_secundario' => ['panturrilha', 'quadriceps', 'gluteos'],
                'tipo_medicao' => 'tempo_e_distancia',
                'equipamento' => 'pista_atletismo',
                'instrucoes_execucao' => 'Séries em alta intensidade (ex: 10x 400m ou 5x 1000m) em Z4/Z5 com intervalos de descanso ativo ou parado.',
            ],
            [
                'nome' => 'Tempo Run / Corrida em Ritmo de Prova',
                'categoria' => 'corrida',
                'grupo_muscular_primario' => 'cardiovascular',
                'grupo_muscular_secundario' => ['quadriceps', 'panturrilha'],
                'tipo_medicao' => 'tempo_e_distancia',
                'equipamento' => 'asfalto',
                'instrucoes_execucao' => 'Corrida sustentada no limiar de lactato (Z3/Z4), simulando pace alvo de 5k ou 10k.',
            ],
            [
                'nome' => 'Longão (Long Run)',
                'categoria' => 'corrida',
                'grupo_muscular_primario' => 'cardiovascular',
                'grupo_muscular_secundario' => ['corpo_inteiro'],
                'tipo_medicao' => 'tempo_e_distancia',
                'equipamento' => 'asfalto',
                'instrucoes_execucao' => 'Treino de maior volume semanal para ganho de resistência muscular e mitocondrial prolongada.',
            ],
            [
                'nome' => 'Caminhada Inclinada na Esteira',
                'categoria' => 'corrida',
                'grupo_muscular_primario' => 'cardiovascular',
                'grupo_muscular_secundario' => ['panturrilha', 'gluteos'],
                'tipo_medicao' => 'tempo_e_distancia',
                'equipamento' => 'esteira',
                'instrucoes_execucao' => 'Inclinação de 8-12%, velocidade 5 a 6 km/h. Excelente cardio de baixo impacto para articulações.',
            ],

            // ─── ALONGAMENTO & MOBILIDADE ─────────────────────
            [
                'nome' => 'Mobilidade de Quadril 90/90',
                'categoria' => 'alongamento',
                'grupo_muscular_primario' => 'gluteos',
                'grupo_muscular_secundario' => ['flexores_quadril'],
                'tipo_medicao' => 'tempo_apenas',
                'equipamento' => 'peso_corporal',
                'instrucoes_execucao' => 'Sentado no chão com ambas as pernas formando ângulo de 90 graus. Alterne os lados trabalhando rotação interna e externa.',
            ],
            [
                'nome' => 'Mobilidade de Tornozelo na Parede',
                'categoria' => 'alongamento',
                'grupo_muscular_primario' => 'panturrilha',
                'grupo_muscular_secundario' => ['tornozelo'],
                'tipo_medicao' => 'tempo_apenas',
                'equipamento' => 'livre',
                'instrucoes_execucao' => 'Aproxime o joelho da parede sem descolar o calcanhar do chão, melhorando a dorsiflexão para o agachamento.',
            ],
            [
                'nome' => 'Gato-Vaca (Cat-Cow Thoracic)',
                'categoria' => 'alongamento',
                'grupo_muscular_primario' => 'costas',
                'grupo_muscular_secundario' => ['lombar', 'abdomen'],
                'tipo_medicao' => 'tempo_apenas',
                'equipamento' => 'peso_corporal',
                'instrucoes_execucao' => 'Em 4 apoios, alterne entre arquear a coluna para cima expirando e descer o abdome inspirando.',
            ],
            [
                'nome' => 'Alongamento Dinâmico de Isquiotibiais',
                'categoria' => 'alongamento',
                'grupo_muscular_primario' => 'isquiotibiais',
                'grupo_muscular_secundario' => ['gluteos'],
                'tipo_medicao' => 'tempo_apenas',
                'equipamento' => 'livre',
                'instrucoes_execucao' => 'Pêndulo de perna frontal e posterior controlado para liberar a cadeia posterior antes da corrida.',
            ],
            [
                'nome' => 'Alongamento de Peitoral no Batente / Parede',
                'categoria' => 'alongamento',
                'grupo_muscular_primario' => 'peito',
                'grupo_muscular_secundario' => ['deltoide_anterior'],
                'tipo_medicao' => 'tempo_apenas',
                'equipamento' => 'livre',
                'instrucoes_execucao' => 'Apoie o antebraço a 90 graus na quina e gire suavemente o tronco para o lado oposto sustentando por 30 segundos.',
            ],
            [
                'nome' => 'Couch Stretch (Alongamento Profundo de Psoas)',
                'categoria' => 'alongamento',
                'grupo_muscular_primario' => 'quadriceps',
                'grupo_muscular_secundario' => ['flexores_quadril'],
                'tipo_medicao' => 'tempo_apenas',
                'equipamento' => 'livre',
                'instrucoes_execucao' => 'Joelho encostado na quina da parede/sofá e canela vertical. Mantenha tronco ereto contraindo o glúteo do lado apoiado.',
            ],
        ];

        foreach ($exercises as $data) {
            $data['slug'] = Str::slug($data['nome']);
            Exercise::updateOrCreate(
                ['nome' => $data['nome']],
                $data
            );
        }
    }
}
