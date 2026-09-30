You are working inside a Laravel 12 + Filament 3 project for
Iceland Beach Resort. Production: icelandbeach.com

TASK: In the Filament admin dashboard booking form (manual bookings
created by staff), make email optional. Phone number becomes the
primary identifier. Email stays optional — if provided, confirmation
email sends. If not provided, no email sent, no error.

The PUBLIC booking form (rooms.jsx) is UNTOUCHED — email stays
required there for Paystack payment flow.

=== STEP 0: READ BEFORE TOUCHING ANYTHING ===

1. `cat app/Filament/Resources/BookingResource.php`
2. `grep -rn "guest_email\|email.*required\|required.*email" app/Filament/Resources/BookingResource.php`
3. `grep -rn "guest_phone\|phone" app/Filament/Resources/BookingResource.php`
4. `grep -rn "guest_email\|guest_phone" app/Services/ReservationService.php`
5. `grep -rn "guest_email\|sendEmail\|BookingCustomer\|BookingNotification" app/Services/ReservationService.php`
6. `php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('bookings'));"`

Read ALL outputs before writing anything.

=== TASK 1: Make Email Optional in BookingResource Form ===

In app/Filament/Resources/BookingResource.php, find the
guest_email field. It likely looks like:

```php
TextInput::make('guest_email')
    ->email()
    ->required()
```

Change to:
```php
TextInput::make('guest_email')
    ->label('Email (Optional)')
    ->email()
    ->nullable()
    ->helperText('If provided, a confirmation email will be sent.')
    // Remove ->required() entirely
```

=== TASK 2: Make Phone Number Required + Primary ID ===

Find the guest_phone field in BookingResource form.
It likely has ->nullable() or no ->required().

Change to:
```php
TextInput::make('guest_phone')
    ->label('Phone Number')
    ->tel()
    ->required()
    ->helperText('Primary contact. Used to identify the guest.')
```

Also ensure phone shows prominently in the table columns.
Find the table() method and make sure guest_phone appears
as an early column — move it before email if email is shown:

```php
Tables\Columns\TextColumn::make('guest_phone')
    ->label('Phone')
    ->searchable()
    ->copyable(),
```

=== TASK 3: Fix Validation in BookingController / ReservationService ===

STEP 1 — Find where manual Filament bookings are saved.
Could be in:
- BookingResource form directly (Filament handles save)
- A custom BookingController
- ReservationService::requestBooking()

Check:
```bash
grep -rn "guest_email\|required\|validate" app/Filament/Resources/BookingResource.php | head -20
```

STEP 2 — If Filament handles the save directly (no custom
controller), the form validation change in Task 1 is enough.
Filament respects ->required() / ->nullable() on form fields.

STEP 3 — If there is a custom store() method or the booking
goes through ReservationService, find the validation rules:
```php
'guest_email' => ['required', 'email'],
```

Change to:
```php
'guest_email' => ['nullable', 'email'],
```

=== TASK 4: Guard Email Sending Against Null Email ===

Find everywhere a booking confirmation email is sent.
Search:
```bash
grep -rn "BookingCustomerConfirmationMail\|BookingNotificationMail\|Mail::to\|guest_email" app/Services/ReservationService.php app/Http/Controllers --include="*.php"
```

For EVERY place that sends to guest_email, wrap with a null check:

BEFORE:
```php
Mail::to($booking->guest_email)
    ->send(new BookingCustomerConfirmationMail(...));
```

AFTER:
```php
if (filled($booking->guest_email)) {
    Mail::to($booking->guest_email)
        ->send(new BookingCustomerConfirmationMail(...));
}
```

Also in ReservationService::sendBookingEmails():
```php
// BEFORE:
if (! empty($room->email)) {
    Mail::to($room->email)->send(new BookingCustomerConfirmationMail($room, $phone, $guests));
}

// AFTER — already guarded, just confirm it exists and is correct
if (filled($room->email)) {
    Mail::to($room->email)->send(new BookingCustomerConfirmationMail($room, $phone, $guests));
}
```

=== TASK 5: Update PendingBookingsWidget + ConfirmedBookingsWidget ===

In both widgets, the guest_email column may show blank for
manual bookings. Make it graceful:

```php
Tables\Columns\TextColumn::make('guest_email')
    ->label('Email')
    ->placeholder('No email')  // shows "No email" instead of blank
    ->searchable()
    ->toggleable(),

Tables\Columns\TextColumn::make('guest_phone')
    ->label('Phone')
    ->placeholder('No phone')
    ->searchable()
    ->copyable(),
```

=== TASK 6: Ensure Phone is Searchable Across Admin ===

In BookingResource table(), make sure ->searchable() is on
guest_phone so staff can look up a guest by phone number:

```php
Tables\Columns\TextColumn::make('guest_phone')
    ->label('Phone')
    ->searchable()
    ->copyable()
    ->icon('heroicon-o-phone'),
```

In the global Filament search, phone should return bookings.
Add to BookingResource:
```php
protected static array $searchableAttributes = [
    'guest_name',
    'guest_phone',  // primary ID
    'guest_email',  // optional
];
```

Or if the resource uses getGloballySearchableAttributes():
```php
public static function getGloballySearchableAttributes(): array
{
    return ['guest_name', 'guest_phone', 'guest_email'];
}
```

=== FINAL CHECKS ===
1. `php artisan optimize:clear && php artisan route:cache`
2. Open /admin/bookings/create
3. Confirm: email field is optional, phone field is required
4. Create a manual booking with phone only (no email)
5. Confirm: booking saves successfully, no error
6. Confirm: no email attempt, no 500 error
7. Create a manual booking with both phone and email
8. Confirm: booking saves, confirmation email arrives
9. Check pending bookings widget — phone shows, email shows "No email" for phone-only bookings
10. Search by phone number in admin — booking appears

```bash
git add app/Filament/Resources/BookingResource.php
git add app/Filament/Widgets/PendingBookingsWidget.php
git add app/Filament/Widgets/ConfirmedBookingsWidget.php
git add app/Services/ReservationService.php
git commit -m "feat: make email optional for manual bookings, phone as primary ID"
git push origin main
```

DO NOT touch:
- rooms.jsx public booking form (email stays required there)
- Paystack payment flow (needs email)
- ticket.php or ticket purchase form
- Any existing role guards