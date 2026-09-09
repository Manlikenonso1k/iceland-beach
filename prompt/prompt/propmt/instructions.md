Restrict the Front Desk and Scanner roles in the Filament admin panel.

First, inspect how roles are currently implemented in this codebase before
changing anything. There is already a Referral (Partner) role. Report what
you find — spatie/laravel-permission, an enum, a column on users, or Filament
policies — and use that same mechanism. Do not install a new permissions
package if one already exists.

Required outcome:
- Front Desk: no access to the Administration section and no access to
  Events. Both must be hidden from navigation AND blocked at the resource
  level (canViewAny/canCreate/canEdit/canDelete returning false), so a
  direct URL cannot reach them.
- Scanner: same restrictions as Front Desk — no Administration, no Events.
- Do not change what these roles can currently do elsewhere (ticket
  scanning, bookings) — only remove the two areas named above.

Implementation requirements:
- Hiding a nav item is not access control. Every restriction must also be
  enforced server-side on the resource or policy, and on any custom pages
  or widgets belonging to those sections.
- Check for widgets registered on the dashboard that surface Administration
  or Events data, and hide those for these two roles as well.
- Do not create a separate panel or a separate login route. All roles keep
  using the existing /admin login.
- Do not weaken the existing scoping on the Referral role.

Verification:
- Tests: a Front Desk user and a Scanner user each receive 403 when
  requesting the Administration and Events resource URLs directly, and
  neither section appears in their navigation. Existing role tests must
  still pass.
- List every file changed and confirm no route definitions were modified.