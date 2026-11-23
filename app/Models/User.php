<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_owner' => 'boolean',
            'modulos' => 'array',
            'concursos_plano' => 'boolean',
        ];
    }

    /** Segue o planejamento dirigido do concurso? Sem ele, o módulo de concursos mostra só Resumos e Leis. */
    public function temPlanoConcursos(): bool
    {
        return (bool) ($this->concursos_plano ?? true);
    }

    /**
     * Pode abrir o módulo? O dono sempre pode; modulos = null libera todos.
     * Chaves válidas em config/modulos.php.
     */
    public function podeAcessar(string $modulo): bool
    {
        if ($this->is_owner || $this->modulos === null) {
            return true;
        }

        return in_array($modulo, $this->modulos, true);
    }

    /**
     * Get the user's salary profiles.
     */
    public function salaryProfiles(): HasMany
    {
        return $this->hasMany(SalaryProfile::class);
    }

    /**
     * Holerites reais gravados para a aba Real × Projetado.
     */
    public function salaryPayslips(): HasMany
    {
        return $this->hasMany(SalaryPayslip::class);
    }

    /**
     * Relacionamentos do Módulo de Exercícios Físicos
     */
    public function workoutSessions(): HasMany
    {
        return $this->hasMany(WorkoutSession::class);
    }

    public function workoutPlans(): HasMany
    {
        return $this->hasMany(WorkoutPlan::class);
    }

    public function bodyMetrics(): HasMany
    {
        return $this->hasMany(UserBodyMetric::class)->orderByDesc('data_medicao');
    }

    public function gearItems(): HasMany
    {
        return $this->hasMany(GearItem::class);
    }

    public function exercisePersonalRecords(): HasMany
    {
        return $this->hasMany(ExercisePersonalRecord::class);
    }

    public function workoutGoals(): HasMany
    {
        return $this->hasMany(WorkoutGoal::class);
    }

    public function customExercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }
}

