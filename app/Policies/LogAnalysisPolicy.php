<?php

namespace App\Policies;

use App\Models\LogAnalysis;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LogAnalysisPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Any authenticated user can view their own analyses
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LogAnalysis $logAnalysis): bool
    {
        return $user->id === $logAnalysis->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create log analyses
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LogAnalysis $logAnalysis): bool
    {
        return $user->id === $logAnalysis->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LogAnalysis $logAnalysis): bool
    {
        return $user->id === $logAnalysis->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LogAnalysis $logAnalysis): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LogAnalysis $logAnalysis): bool
    {
        return false;
    }
}
