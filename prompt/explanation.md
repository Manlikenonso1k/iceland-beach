# Iceland Beach Ticket System Explanation

This document explains the core workflow of the Laravel ticket system and how the QR code generation and scanning process works.

## 1. How the Laravel Ticket System Works

Laravel is a PHP framework that handles the backend logic, routing, and database interactions for the ticket system. Here is the lifecycle of a ticket purchase:

1. **Purchase & Payment:** 
   - A customer visits the ticket purchase page and fills out their details.
   - They initiate a payment via a gateway (like Paystack or TGI Pay).
   - Once the payment is successful, the payment gateway sends a callback/webhook to the Laravel backend (`PaymentController::paystackVerify` or `tgiPayWebhook`).

2. **Ticket Creation:**
   - Laravel verifies the payment directly with the gateway to prevent fraud (it never trusts the frontend).
   - It then creates a `Ticket` record in the database, generating a unique `uuid` (Universally Unique Identifier) to prevent anyone from guessing a ticket number.
   - The system marks the ticket as `paid = true`.

3. **Email & Receipt:**
   - Immediately after the ticket is saved, Laravel triggers an email containing the receipt.
   - The user is redirected to the `success.blade.php` page, where they can view, download, or print their ticket.

---

## 2. How the QR Code Works

The QR code is the digital signature of the ticket, allowing gate operators to securely check guests in.

### A. Generation
- When a ticket is created, the `PaymentController::generateQr()` method is called.
- It uses the `simplesoftwareio/simple-qrcode` package to generate an SVG image.
- **The Data:** The data embedded inside the QR code is simply a secure, unique URL pointing back to your application: `https://[your-domain]/ticket/verify/{uuid}`.
- The SVG file is saved to the server's storage (`storage/app/public/qrcodes/`) so it can be loaded directly on the success page and attached to emails.

### B. Scanning (Check-In)
- **Scanning:** At the event gate, the admin uses a standard mobile phone camera or a hardware barcode scanner to scan the guest's QR code.
- **Redirection:** The scanner simply reads the URL (`.../ticket/verify/{uuid}`) and opens it in the admin's web browser.
- **Authentication:** If the admin is not logged in to the admin panel, Laravel forces them to log in first (ensuring only authorized staff can check in guests).
- **Verification Logic:**
  - `PaymentController::verifyEntry()` processes the request.
  - It looks up the ticket in the database by its `uuid`.
  - It increments the `scan_count` (which tracks if someone tries to scan the same screenshot multiple times).
  - **Checks:**
    - *If unpaid:* Returns an "unpaid" error.
    - *If already checked in:* Returns an "already scanned" warning.
    - *If valid:* It marks `checked_in = true`, saves the current timestamp (`checked_in_at`), and logs the check-in to the `TicketScanLog` table, tying it to the admin's user ID.
- **Confirmation:** The admin sees a success message on their screen, and the guest is allowed entry!
