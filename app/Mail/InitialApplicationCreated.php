<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InitialApplicationCreated extends Mailable
{
    use Queueable, SerializesModels;
 
    public $application;
    public $signedUrl;

    public function __construct(Application $application, $signedUrl)
    {
        $this->application = $application;
        $this->signedUrl = $signedUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action Required: Complete Your Franchise Application',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.applications.initial', // We will create this view
        );
    }
}