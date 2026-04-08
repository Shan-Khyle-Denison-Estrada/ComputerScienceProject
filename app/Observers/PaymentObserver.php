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

            // Trigger the check for any application type that requires pre-review payment
            if (in_array($application->application_type, ['New Franchise', 'Change of Owner', 'Change of Owner (Deceased)', 'Change of Unit'])) {
                $appObserver = new ApplicationObserver();
                $appObserver->checkAndNotifyReviewer($application);
            }
        }
    }
}