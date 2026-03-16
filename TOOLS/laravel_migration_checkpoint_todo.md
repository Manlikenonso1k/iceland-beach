# Laravel Migration Checkpoint (2026-03-16)

## Confirmed Done

- Laravel app exists in `laravel-app/` with Filament panel provider and resource/widget auto-discovery.
- Legacy core tables are migrated with Laravel conventions (timestamps + soft deletes):
  - `room`, `services`, `membership`, `products`, `waiters`, `tables`, `sales`, `sale_items`, `void_logs`.
- Eloquent models created for migrated entities:
  - `Room`, `ServiceBooking`, `Membership`, `Product`, `InventoryItem`, `Waiter`, `DiningTable`, `Sale`, `SaleItem`, `VoidLog`.
- Filament resources scaffolded and present:
  - `BookingResource`, `InventoryItemResource`, `SaleResource`, `ReportResource`.
- Mailing logic has been moved into Laravel Mailables for booking + receipts:
  - `BookingNotificationMail`, `BookingCustomerConfirmationMail`, `SaleReceiptMail`.
- Filament actions for notifications are present:
  - `SaleResource` has `Send Receipt` action.
  - `BookingResource` has `Send Booking Mail` action.
- POS/reporting helper service exists:
  - `PosService` with duplicate-window helper and report summary aggregation.
- Reporting widgets exist and are attached to report list header:
  - `SalesOverview`, `PaymentMethodBreakdown`.

## Partially Done / Risky Gaps

- Booking overlap guard exists in `ReservationService`, but it is not wired to BookingResource create/edit flow.
- `AdminPanelProvider` only explicitly registers default Filament widgets (`AccountWidget`, `FilamentInfoWidget`); custom widgets are discovered, but dashboard placement strategy is not finalized.
- `ReportResource` has scaffold page classes for create/edit still in tree, while resource routes only expose index.
- `composer.json` is locally modified (`git status` shows it as changed), so package-state may be mid-flight.

## Remaining Work (High Priority)

- Wire booking create/edit to `ReservationService::reserveRoom()` so overlap validation and standardized email flow are always enforced.
- Implement room/service/membership expiry lifecycle jobs (legacy had auto-expire + email + reset logic in admin flow).
- Implement POS transaction workflow in Laravel (not only resource CRUD):
  - table assignment guard
  - duplicate-sale prevention window
  - stock decrement for non-voided items
  - mandatory void reason logging
  - table status transitions to billing/available
- Move sales/report filtering logic from legacy SQL parity into Filament filters + query scopes/service methods.
- Add missing Filament Infolists/Detail views where legacy operator context is needed (sale items, void markers, waiter/table context).
- Add tests for edge cases:
  - overlap booking rejection
  - duplicate-sale window
  - insufficient stock
  - void reason required
  - expiry transitions
  - mail dispatch

## Suggested Next Step Order

1. Finish transactional POS service and call it from `SaleResource` actions/forms.
2. Wire booking lifecycle through `ReservationService` and enforce overlap checks on save.
3. Add scheduled commands/jobs for expiry + email resets.
4. Add PHPUnit feature tests for all legacy edge cases.
5. Clean up scaffold leftovers (unused report pages) and align dashboard widgets placement.
