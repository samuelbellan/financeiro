<?php

declare(strict_types=1);

namespace App\DTOs;

class FilhoDTO
{
    public function __construct(
        public string $nome,
        public string $dt_nascimento, // YYYY-MM-DD
        public int $idade_escola = 2   // Years
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nome: $data['nome'] ?? 'Filho',
            dt_nascimento: $data['dt_nascimento'] ?? date('Y-m-d'),
            idade_escola: (int) ($data['idade_escola'] ?? 2)
        );
    }
}
