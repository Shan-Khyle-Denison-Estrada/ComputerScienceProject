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
        'view_assessments',
        'store_assessments',
        'generate_reports',
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
        'view_franchises_index',
        'view_franchise_details',
        'print_franchise_certificate',
        'print_qr_badge',
        'manage_certificate_template',
        'view_applications_index',
        'finalize_applications',
        'manage_drivers',
        'manage_franchise_drivers',
        'manage_issues', // Complaints and Red Flags
        'view_assessments',
        'store_assessments',
        'generate_reports',
    ],
    'collector' => [
        'view_payments_index',
        'store_payments',        // Creating new payments
        'view_payment_details',  // Opening the view modal
        'print_payments',        // Generating/Printing the receipt
    ],
    'releaser' => [
        'view_franchises_index',
        'view_franchise_details',
        'print_franchise_certificate',
        'print_qr_badge',
    ],
    'encoder' => [
        // Encoders only get to view, not print or manage templates
        'view_franchises_index',
        'view_franchise_details',
        'view_applications_index',
        'view_application_details',
        'evaluate_requirements',
        'finalize_applications',
        'manage_drivers',
        'manage_franchise_drivers',
        'manage_issues', // Complaints and Red Flags
        'view_assessments',
        'store_assessments',
        'generate_reports',
    ],
];