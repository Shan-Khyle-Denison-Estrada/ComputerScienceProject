<?php

namespace App\Mail;

use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssessmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $assessment;

    public function __construct(Assessment $assessment)
    {
        $this->assessment = $assessment;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Assessment Notice: ASM-' . $this->assessment->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.assessment_notification',
        );
    }
}