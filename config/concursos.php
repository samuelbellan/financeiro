<?php

return [
    /*
    | Liga o módulo novo de concursos (meta diária, questões, curadoria).
    | true só no PC servidor, onde está o Postgres local; false na nuvem,
    | onde as rotas do módulo respondem 404 e o menu o esconde.
    | (O app estático antigo em /concursos não depende desta flag.)
    */
    'enabled' => (bool) env('CONCURSOS_ENABLED', false),

    'connection' => 'concursos_pgsql',

    // Python com pdfplumber (scripts/requirements-concursos.txt), usado para ler os PDFs do TEC.
    'python' => env('CONCURSOS_PYTHON', base_path(PHP_OS_FAMILY === 'Windows'
        ? '.venv-concursos/Scripts/python.exe'
        : '.venv-concursos/bin/python')),

    // Ligação com o cronômetro do /estudos (na nuvem, em services.estudos.cloud_url).
    // O token é o da API de leitura, criado em /estudos → Dados → Token da API.
    'estudos' => [
        'token' => env('CONCURSOS_ESTUDOS_TOKEN'),
    ],

    // Motor da meta do dia (determinístico). Ver App\Services\Concursos\MotorMeta.
    'motor' => [
        'minutos_por_questao' => 3,
        'questoes_por_bloco' => 10,
        'minutos_teoria' => 40,
        'revisao_max_pct' => 30,          // revisão de erros ocupa no máximo isso do dia
        'revisao_intervalos' => [1, 3, 7, 15, 30], // dias até rever, por acerto seguido
        'janela_dias' => 60,              // tentativas consideradas no domínio
        'rede_minimo' => 10,              // rede de segurança: com 10+ questões...
        'rede_acerto' => 0.5,             // ...e acerto abaixo disso, sugere Teoria
        'prior' => ['nao_vi' => 0.1, 'vi_parte' => 0.4, 'vi_tudo' => 0.6, '' => 0.3], // '' = não declarou
    ],

    // Leis em Markdown do projeto ctresb/leis (github.com/ctresb/leis). O repositório inteiro tem
    // vários GB, então só as leis do catálogo são baixadas (sob demanda) e guardadas em `dir`.
    // No material de resumo, [[ctn 98]] vira link para o artigo; a chave é a sigla do catálogo.
    'leis' => [
        'raw' => env('CONCURSOS_LEIS_RAW', 'https://raw.githubusercontent.com/ctresb/leis/main/'),
        'web' => 'https://github.com/ctresb/leis/blob/main/',
        'dir' => \App\Support\Dados::raiz() . DIRECTORY_SEPARATOR . 'concursos' . DIRECTORY_SEPARATOR . 'leis',
        // Cópia completa do repositório (git clone --depth 1). Com ela, o app lê tudo do disco,
        // abre qualquer norma federal pelo número e navega pelo acervo inteiro.
        'local' => env('CONCURSOS_LEIS_LOCAL'),
        'catalogo' => [
            'cf' => ['sigla' => 'CF', 'nome' => 'Constituição Federal de 1988', 'grupo' => 'Normas Constitucionais', 'path' => 'constituicao.md'],
            // O ADCT está no mesmo arquivo da CF; os artigos dele ficam com âncora "adct-art-N".
            'adct' => ['sigla' => 'ADCT', 'nome' => 'Ato das Disposições Constitucionais Transitórias', 'grupo' => 'Normas Constitucionais', 'path' => 'constituicao.md', 'de' => 'cf', 'prefixo' => 'adct-'],
            'ec132' => ['sigla' => 'EC 132/2023', 'nome' => 'Emenda Constitucional 132/2023 (Reforma Tributária)', 'grupo' => 'Normas Constitucionais', 'path' => 'federal/emendas-constitucionais/2023/emc-132-2023-12-20.md'],
            'ctn' => ['sigla' => 'CTN', 'nome' => 'Código Tributário Nacional (Lei 5.172/1966)', 'grupo' => 'Normas Gerais e Complementares Tributárias', 'path' => 'federal/leis-ordinarias/1966/lei-5172-1966-10-25.md'],
            'lc87' => ['sigla' => 'LC 87/1996', 'nome' => 'Lei Kandir (ICMS)', 'grupo' => 'Normas Gerais e Complementares Tributárias', 'path' => 'federal/leis-complementares/1996/lcp-87-1996-09-13.md'],
            'lc116' => ['sigla' => 'LC 116/2003', 'nome' => 'Lei Complementar 116/2003 (ISS)', 'grupo' => 'Normas Gerais e Complementares Tributárias', 'path' => 'federal/leis-complementares/2003/lcp-116-2003-07-31.md'],
            'lc123' => ['sigla' => 'LC 123/2006', 'nome' => 'Estatuto da Microempresa (Simples Nacional)', 'grupo' => 'Normas Gerais e Complementares Tributárias', 'path' => 'federal/leis-complementares/2006/lcp-123-2006-12-14.md'],
            // LC 214/2025 (IBS/CBS) fica de fora: no acervo só existe o texto das partes vetadas promulgadas.
            'lef' => ['sigla' => 'LEF', 'nome' => 'Lei de Execução Fiscal (Lei 6.830/1980)', 'grupo' => 'Legislação Processual e Empresarial', 'path' => 'federal/leis-ordinarias/1980/lei-6830-1980-09-22.md'],
            'lei11101' => ['sigla' => 'Lei 11.101/2005', 'nome' => 'Lei de Falências e Recuperação Judicial', 'grupo' => 'Legislação Processual e Empresarial', 'path' => 'federal/leis-ordinarias/2005/lei-11101-2005-02-09.md'],
            'lei9492' => ['sigla' => 'Lei 9.492/1997', 'nome' => 'Lei de Protesto de Títulos', 'grupo' => 'Legislação Processual e Empresarial', 'path' => 'federal/leis-ordinarias/1997/lei-9492-1997-09-10.md'],
            'ctb' => ['sigla' => 'CTB', 'nome' => 'Código de Trânsito Brasileiro (Lei 9.503/1997)', 'grupo' => 'Legislação de Trânsito', 'path' => 'federal/leis-ordinarias/1997/lei-9503-1997-09-23.md'],
        ],
    ],

    'backup' => [
        // Pasta sincronizada com o Google Drive onde o pg_dump (de hora em hora, via backup:drive) é salvo.
        'dir' => env('CONCURSOS_BACKUP_DIR'),
        // Retenção: os N mais recentes, depois 1 por dia por D dias e 1 por semana por S semanas.
        'recentes' => (int) env('CONCURSOS_BACKUP_RECENTES', 24),
        'diarios' => (int) env('CONCURSOS_BACKUP_DIARIOS', 14),
        'semanais' => (int) env('CONCURSOS_BACKUP_SEMANAIS', 8),
        // Banco onde o backup:testar restaura o dump toda semana (criado por scripts/backup-teste-setup.ps1).
        'teste_db' => env('CONCURSOS_BACKUP_TESTE_DB', 'concursos_restauracao'),
        // Pasta do pg_dump.exe (vazio = usa o PATH).
        'pg_bin' => env('CONCURSOS_PG_BIN', 'C:\\Program Files\\PostgreSQL\\18\\bin'),
    ],

    // Cadernos novos da pasta do TEC (scripts/concursos/vigiar_tec.js → receptor → concursos:tec-novos).
    // Cada subpasta de storage/concursos/tec é de um material; o prefixo vai na chave da seção
    // (o Tributário tem os cadernos "Expert" em c01… e os demais em tc01…). A mesma tabela
    // de siglas → subpasta está no vigiar_tec.js.
    'tec' => [
        'pasta' => env('CONCURSOS_TEC_PASTA', 5145567),
        'subpastas' => [
            'aud' => ['material' => 'aud', 'prefixo' => ''],
            'ctbga' => ['material' => 'ctbga', 'prefixo' => ''],
            'dadm' => ['material' => 'dadm', 'prefixo' => ''],
            'dciv' => ['material' => 'dciv', 'prefixo' => ''],
            'dcon' => ['material' => 'dcon', 'prefixo' => ''],
            'demp' => ['material' => 'demp', 'prefixo' => ''],
            'dpen' => ['material' => 'dpen', 'prefixo' => ''],
            'dtrib-expert' => ['material' => 'dtrib', 'prefixo' => ''],
            'dtrib' => ['material' => 'dtrib', 'prefixo' => 't'],
            'mfin' => ['material' => 'mfin', 'prefixo' => ''],
            'port' => ['material' => 'port', 'prefixo' => ''],
            'rlog' => ['material' => 'rlog', 'prefixo' => ''],
        ],
    ],
];
