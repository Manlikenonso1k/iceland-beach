There were 3 failed login attempts since the last successful login.
[u519226541@us-phx-web940 ~]$ cd domains/icelandbeach.com/public_html
[u519226541@us-phx-web940 public_html]$ cd staging
[u519226541@us-phx-web940 staging]$ ls
 add_placeholders.py   bootstraping.txt   context.md       graphify-out              phpunit.xml   resources   skills-lock.json   TODO.md
 app                   composer.json      database        'Iceland - Shortcut.lnk'   prompt        RESULT.md   storage            vendor
 artisan               composer.lock      default.php      package.json              public        routes      strip.png          vite.config.js
 bootstrap             config             FILAMENT_UI.md   package-lock.json         README.md     shot.mjs    tests
[u519226541@us-phx-web940 staging]$ curl -s -o /dev/null -w "HTTP %{http_code}\n" https://staging.icelandbeach.com/api/services
curl -s https://staging.icelandbeach.com/api/services | head -c 400
HTTP 500
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Server Error</title>

        <style>
            /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}a{background-color:transparent}code{font[u519226541@us-phx-web940 staging]$ php artisan tinker --execute="DB::table('services')->update(['deleted_at' => null]); echo 'restored';"php artisan tinker --execute="DB::table('services')->update(['deleted_at' => null]); echo 'restored';"

   Illuminate\Database\QueryException  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'deleted_at' in 'SET' (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: u519226541_stagingice, SQL: update `services` set `deleted_at` = ?).

[u519226541@us-phx-web940 staging]$ php artisan migrate:status | tail -20
php artisan migrate --force
  2026_03_19_130000_create_invoices_table ............................................................................................... [13] Ran  
  2026_03_19_130100_create_invoice_items_table .......................................................................................... [13] Ran  
  2026_03_22_150000_create_activity_logs_table .......................................................................................... [14] Ran  
  2026_03_31_000001_create_bookings_table ............................................................................................... [15] Ran  
  2026_06_11_000001_create_tickets_table ................................................................................................ [15] Ran  
  2026_06_27_000001_add_scan_count_to_tickets_table ..................................................................................... [16] Ran  
  2026_07_01_102310_create_ticket_scan_logs_table ....................................................................................... [17] Ran  
  2026_07_29_140000_create_vendor_spots_table ........................................................................................... [20] Ran  
  2026_07_29_140100_create_vendor_requests_table ........................................................................................ [20] Ran  
  2026_07_29_152631_add_pickup_location_to_tickets_table ................................................................................ [21] Ran  
  2026_08_24_151413_create_personal_access_tokens_table ................................................................................. [25] Ran  
  2026_08_29_160337_add_room_id_and_check_out_to_bookings_table ......................................................................... [26] Ran  
  2026_08_29_160338_add_email_opt_in_to_bookings_table .................................................................................. [26] Ran  
  2026_08_29_160803_create_referrals_table .............................................................................................. [26] Ran  
  2026_08_29_160805_create_referral_ticket_sales_table .................................................................................. [26] Ran  
  2026_08_29_160807_add_referral_code_to_tickets_table .................................................................................. [26] Ran  
  2026_08_29_172928_add_images_to_room_table ............................................................................................ [26] Ran  
  2026_08_29_181139_add_commission_to_referrals_and_sales ............................................................................... [26] Ran  
  2026_08_29_181320_add_images_to_services_table ........................................................................................ [26] Ran  


   INFO  Nothing to migrate.  

[u519226541@us-phx-web940 staging]$ php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
