<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

/**
 * Login page for the admin panel.
 *
 * Behaviour is Filament's own — only the presentation differs. The
 * geothermal-coast styling and brand markup are injected through render
 * hooks registered in AdminPanelProvider and scoped to this class, so the
 * Livewire form (validation, throttling, session) is left untouched.
 */
class Login extends BaseLogin
{
    // Filament renders heading + subheading in .fi-simple-header, which the
    // login theme hides in favour of our own brand block.
    public function getHeading(): string
    {
        return '';
    }
}
