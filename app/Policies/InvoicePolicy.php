<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    protected const ADMIN_EMAIL = 'victorynonso9@gmail.com';

    public function before(User $user, string $ability): ?bool
    {
        if (in_array($ability, ['delete', 'deleteAny', 'forceDelete', 'forceDeleteAny'], true)) {
            return null;
        }

        if ($this->isInvoiceAdmin($user)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $this->canManageInvoices($user);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->canManageInvoices($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageInvoices($user);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->canManageInvoices($user);
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
        return $this->canManageInvoices($user);
    }

    public function reorder(User $user): bool
    {
        return false;
    }

    protected function canManageInvoices(User $user): bool
    {
        return $this->isInvoiceAdmin($user)
            || $user->hasAnyRole(['Front Desk', 'frontdesk']);
    }

    protected function isInvoiceAdmin(User $user): bool
    {
        return $user->email === self::ADMIN_EMAIL
            || $user->hasAnyRole(['Super Admin', 'super_admin', 'admin']);
    }
}
