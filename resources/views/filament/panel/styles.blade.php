{{--
    Panel-wide CSS, injected via a HEAD_END render hook.

    resources/css/app.css is the PUBLIC React site's stylesheet and is never
    loaded inside the Filament panel, so panel overrides have to be injected
    here (same mechanism as the login theme — see FILAMENT_UI.md).
--}}
<style>
    /*
      Filament's mobile slide-over sidebar is a fixed 320px. On a 320px-wide
      phone that is exactly 100% of the viewport: the page behind is fully
      covered and there is nothing left to tap to dismiss it. Capping the width
      leaves a strip of the closing overlay reachable.
    */
    @media (max-width: 1024px) {
        .fi-sidebar {
            max-width: 280px !important;
            width: 80vw !important;
        }
    }
</style>
