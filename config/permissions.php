<?php

return [
    // We will add other roles here later. For now, just the evaluator.
    'evaluator' => [
        'view_evaluator_applications', // Access to Index.vue (list of apps)
        'view_application_details', // Access to Show*.vue pages
        'evaluate_requirements',    // Checking off requirements & adding remarks
        'return_applications',      // Using the Return Modal
        'reject_applications',      // Using the Reject Modal
        'approve_applications',     // Using the Approve/Forward Modal
    ],
    'inspector' => [
        'view_inspector_applications',
        'view_application_details',
        'inspect_unit',          // Specific to checking off inspection items
        'reject_applications',   // Used in Show pages
        'approve_applications',  // Used in Show pages
    ],
    'city_anti_pollution_officer' => [
        'view_capo_applications',
        'view_application_details',
        'approve_applications',
        'reject_applications', // Also covers returning applications since they use the same route
    ],
    'reviewer' => [
        'view_reviewer_applications',
        'view_application_details',
        'approve_applications',
        'reject_applications', // Also handles returning apps if they share the reject modal
    ],
    'admin' => [
        // We will expand this massively later, but for now, keep their payment access!
        'view_payments_index',
        'store_payments',
        'view_payment_details',
        'print_payments',
    ],
    'collector' => [
        'view_payments_index',
        'store_payments',        // Creating new payments
        'view_payment_details',  // Opening the view modal
        'print_payments',        // Generating/Printing the receipt
    ],
];