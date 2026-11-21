<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perfil extends ConcursosModel
{
    protected $table = 'perfis';

    public const DIAS = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];

    public const DECLARACOES = [
        'nao_vi' => 'Não vi',
        'vi_parte' => 'Vi parte',
        'vi_tudo' => 'Vi tudo',
    ];

    protected function casts(): array
    {
        return ['minutos_semana' => 'array'];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(EditalCargo::class, 'cargo_id');
    }

    public function minutosDoDia(\DateTimeInterface $data): int
    {
        return (int) ($this->minutos_semana[(string) $data->format('N')] ?? 0);
    }

    /** @return array<int, string> topico_id => declaração */
    public function declaracoes(): array
    {
        return \Illuminate\Support\Facades\DB::connection($this->getConnectionName())->table('perfil_topicos')
            ->where('user_id', $this->user_id)->pluck('declarado', 'topico_id')->all();
    }

    public function declarar(int $topicoId, string $declarado): void
    {
        \Illuminate\Support\Facades\DB::connection($this->getConnectionName())->table('perfil_topicos')->updateOrInsert(
            ['user_id' => $this->user_id, 'topico_id' => $topicoId],
            ['declarado' => $declarado, 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
