<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);
        $recipient = (string) config('resort.booking_admin_recipients.0', env('BOOKING_PRIMARY_EMAIL'));
        Mail::raw("New website enquiry\n\nName: {$data['name']}\nEmail: {$data['email']}\n\nMessage:\n{$data['message']}", function ($mail) use ($recipient, $data): void {
            $mail->to($recipient)->replyTo($data['email'], $data['name'])->subject('New Iceland Beach website enquiry');
        });
        return response()->json(['message' => 'Thank you. Your enquiry has been sent to the Iceland Beach team.'], 201);
    }
}