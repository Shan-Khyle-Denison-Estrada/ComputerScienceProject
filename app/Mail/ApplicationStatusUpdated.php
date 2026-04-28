<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $application;
    public $action;
    public $role;
    public $remarks;

    /**
     * Create a new message instance.
     *
     * @param Application $application
     * @param string $action (e.g., Approved, Rejected, Returned)
     * @param string $role (e.g., Evaluator, Inspector)
     * @param string|null $remarks
     */
    public function __construct(Application $application, $action, $role, $remarks = null)
    {
        $this->application = $application;
        $this->action = $action;
        $this->role = $role;
        $this->remarks = $remarks;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Application {$this->action} - {$this->application->reference_number}")
                    ->markdown('emails.application_status_updated');
    }
}