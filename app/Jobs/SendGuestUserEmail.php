<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendGuestUserEmail
{
    protected $user;
    protected $temporaryPassword;

    public function __construct(User $user, $temporaryPassword)
    {
        $this->user = $user;
        $this->temporaryPassword = $temporaryPassword;
    }

    public function handle()
    {
        $emailData = [
            'app_name' => config('app.name'),
            'email' => $this->user->email,
            'password' => $this->temporaryPassword,
        ];

        Mail::send('emails.guest_user', $emailData, function ($message) {
            $message->to($this->user->email)
                ->subject('Bienvenue sur ' . config('app.name'));
        });
    }
}
