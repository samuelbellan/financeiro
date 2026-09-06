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

#[Fillable(['name', 'email', 'password'])]
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
        ];
    }

    /**
     * Get the user's salary profiles.
     */
    public function salaryProfiles(): HasMany
    {
        return $this->hasMany(SalaryProfile::class);
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

