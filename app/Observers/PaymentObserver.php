<?php

namespace App\Observers;

use App\Models\Payment;
use App\Observers\ApplicationObserver;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        // When a payment is made, check if there's an attached assessment and application
        if ($payment->assessment && $payment->assessment->application) {
            $application = $payment->assessment->application;

            // If it's a New Franchise, run our check to see if this payment was the final missing piece
            if ($application->application_type === 'New Franchise') {
                $appObserver = new ApplicationObserver();
                $appObserver->checkAndNotifyReviewer($application);
            }
        }
    }
}