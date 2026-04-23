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
];