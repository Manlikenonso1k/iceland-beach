I am building a vendor spot booking system in a Laravel 11 / Filament v3 project. I need you to generate the code for an end-to-end workflow where a user clicks a spot on an interactive map, submits a request form, and an admin approves it via Filament, which triggers an email with a payment link. 

Here is the exact workflow and technical requirements:

1. Database & Models:
- Update the `VendorSpot` model/migration. Fields: `id`, `event_id`, `name`, `price` (default 50000), `pos_x`, `pos_y`, `status` (enum: 'available', 'pending_approval', 'sold'). 
- Create a `VendorRequest` model/migration. Fields: `id`, `vendor_spot_id` (foreign key), `name`, `email`, `address`, `expected_to_sell` (text), `status` (enum: 'pending', 'approved', 'paid').
- Set up the HasOne / BelongsTo relationships between these two models.
- Create a `VendorSpotSeeder` using these 10 coordinates (calculate pos_x and pos_y as percentages based on the source image dimensions): 
  Spot 1 (904, 279), Spot 2 (878, 283), Spot 3 (844, 282), Spot 4 (817, 279), Spot 5 (786, 283), Spot 6 (645, 279), Spot 7 (616, 280), Spot 8 (583, 279), Spot 9 (551, 281), Spot 10 (528, 279).

2. Frontend (Livewire Component & Blade):
- Render the image map with absolute positioned buttons using the `pos_x` and `pos_y` percentages. 
- When an 'available' spot is clicked, open a Livewire modal containing a form.
- Form fields: Name, Email, Address, and "What are you expected to sell?" (textarea).
- UI/Styling requirement for the form and modal: Strictly use a high-contrast dark aesthetic. The modal background must be black, form titles and headings must be gold, and all input descriptions/labels must be white text. Use Tailwind CSS for this.
- Upon submission: Create the `VendorRequest` record and update the `VendorSpot` status to 'pending_approval' so no one else can click it. Show a success message.

3. Filament Admin Dashboard:
- Create a `VendorRequestResource` so I have a navigation item in the dashboard to see all requests.
- The table should display the spot name, applicant name, what they intend to sell, and the request status.
- Add a custom Filament Table Action called "Approve & Send Payment Link". 
- When this action is clicked: It updates the request status to 'approved' and fires off an email to the applicant's email address.

4. Mail Notification (Hostinger SMTP):
- Generate a Laravel Mailable called `VendorApprovalMail`.
- The email should inform the user that their request to sell [expected_to_sell] at [spot_name] is approved.
- Include a placeholder button/link in the email template for the payment gateway. 
- Note that the spot costs 50,000 NGN. Include this price in the email text.
- Note: My `.env` is already configured for Hostinger SMTP, so just provide the standard Laravel Mail facade usage in the Filament action.

Please provide all migrations, models, the Livewire component, the Filament Resource, and the Mailable class.