<?php

/*
|--------------------------------------------------------------------------
| Módulos do sistema e controle de acesso por usuário
|--------------------------------------------------------------------------
| users.modulos = null libera todos os módulos (dono e gestores).
| Um array (ex.: ["estudos", "concursos"]) libera só aqueles.
| O dono (is_owner) sempre acessa tudo.
|
| "prefixos" são os primeiros segmentos de URL de cada módulo. Rotas fora de
| qualquer prefixo (home, logout, celular...) não são bloqueadas aqui; rotas
| administrativas têm checagem própria (is_admin / middleware owner).
*/

return [
    'disponiveis' => [
        'financas' => [
            'nome' => 'Finanças',
            'prefixos' => ['financas'],
        ],
        'estudos' => [
            'nome' => 'Horas de Estudo',
            'prefixos' => ['estudos'],
        ],
        'concursos' => [
            'nome' => 'Estudo para Concursos',
            'prefixos' => ['concursos'],
        ],
        'salario' => [
            'nome' => 'Projetor Salarial',
            'prefixos' => ['salario'],
        ],
        'treinos' => [
            'nome' => 'Treinos & Exercícios',
            'prefixos' => ['treinos', 'tracker', 'api/tracker'],
        ],
    ],

    // Módulos de quem entra pela primeira vez por convite.
    'padrao_convidado' => ['estudos', 'concursos'],
];
