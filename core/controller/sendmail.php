<?php
require '../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Sendmail {

    public function mailsender($post, $a_message, $u_message, $useremail){

        // ADMIN EMAILS
        $admin_email = "booking@icelandbeach.com"; 
        $admin_subject = "New Booking Notification";
        $admin_body = $a_message;

        // USER EMAIL
        $user_subject = "Your Iceland Beach Resort Booking";
        $user_body = $u_message;

        // Configure PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Hostinger SMTP (NOT Gmail)
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'booking@icelandbeach.com';   
            $mail->Password = 'kOhSRl;=2S';             
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
            $mail->Port = 465; // SSL

            // Sender information (must match $mail->Username)
            $mail->setFrom('booking@icelandbeach.com', 'Iceland Beach Resort');
            $mail->isHTML(true);

            /* --- SEND TO ADMIN --- */
            $mail->addAddress($admin_email, 'Admin');
            $mail->addAddress('v.chinonso@collegeofartslagos.com', 'Developer');
            $mail->addAddress('booking@icelandbeach.com', 'Booking Admin');
            $mail->addAddress('akapo@icelandbeach.com', 'Admin');
            $mail->addAddress('info@icelandbeach.com', 'Admin');
            $mail->Subject = $admin_subject;
            $mail->Body = $admin_body;
            $mail->send();

            /* --- SEND TO USER --- */
            $mail->clearAddresses();
            $mail->addAddress($useremail);
            $mail->Subject = $user_subject;
            $mail->Body = $user_body;
            $mail->send();

            return true;

        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }
    }

    public function message($message) {
        $date = date('Y');

        $message_format = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Iceland Beach Resort</title>

            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background: #e8f5ff;
                    font-family: Arial, sans-serif;
                }

                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: #ffffff;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
                }

                /* Header beach background */
                .header {
                    background-image: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e');
                    background-size: cover;
                    background-position: center;
                    padding: 40px 20px;
                    text-align: center;
                }

                .header img {
                    width: 150px;
                    background: rgba(255,255,255,0.85);
                    padding: 10px 20px;
                    border-radius: 10px;
                }

                .content {
                    padding: 25px;
                    text-align: left;
                    color: #333;
                }

                .content h1,
                .content h2 {
                    text-align: center;
                    color: #0a4d8c;
                }

                .content p {
                    font-size: 16px;
                    line-height: 1.6;
                }

                .cta-button {
                    display: inline-block;
                    margin: 20px auto;
                    padding: 12px 25px;
                    background-color: #0a4d8c;
                    color: white !important;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: bold;
                }

                .footer {
                    background: #f2f6fa;
                    padding: 15px 10px;
                    text-align: center;
                    font-size: 14px;
                    color: #555;
                }

                .footer a {
                    color: #0a4d8c;
                    text-decoration: none;
                }
            </style>
        </head>

        <body>

            <div class='email-container'>

                <!-- Header with Logo -->
                <div class='header'>
                    <img src='https://icelandbeach.com/static/images/img%20(1).png' alt='Iceland Beach Logo'>
                </div>

                <!-- Main Content -->
                <div class='content'>
                    {$message}
                </div>

                <!-- Footer -->
                <div class='footer'>
                    <p>Contact us:</p>
                    <p>
                        <a href='tel:+2348028227526'>+2348028227526</a> |
                        <a href='tel:+2349017029868'>+2349017029868</a>
                    </p>
                    <p>
                        <a href='https://facebook.com'>Facebook</a> |
                        <a href='https://instagram.com'>Instagram</a> |
                        <a href='https://twitter.com'>Twitter</a>
                    </p>
                    <p>&copy; {$date} Iceland Beach Resort. All rights reserved.</p>
                </div>

            </div>

        </body>
        </html>
        ";

        return $message_format;
        }
    }
