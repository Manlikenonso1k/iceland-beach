<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($ability, ['delete', 'deleteAny', 'forceDelete', 'forceDeleteAny'], true)) {
            return null;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'frontdesk']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'frontdesk']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'frontdesk']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'frontdesk']);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'frontdesk']);
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
