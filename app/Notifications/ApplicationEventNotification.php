<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationEventNotification extends Notification
{
    use Queueable;

    public $application;
    public $eventType;

    // We now pass the event type (e.g., 'created', 'approved') along with the application
    public function __construct(Application $application, string $eventType)
    {
        $this->application = $application;
        $this->eventType = $eventType;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $type = $this->application->application_type;
        $applicantName = "{$this->application->first_name} {$this->application->last_name}";
        $franchiseNumber = $this->application->franchise?->franchise_number ?? 'N/A';
        
        // Default values
        $title = "Application Update: {$type}";
        $message = "There is an update on {$applicantName}'s {$type} application.";

        // --- NEW DRIVER MESSAGES ---
        if ($type === 'New Driver') {
            if ($this->eventType === 'created') {
                $title = "New Driver Application";
                $message = "{$applicantName} has submitted a new driver application.";
            } elseif ($this->eventType === 'approved') {
                $title = "Driver Application Approved";
                $message = "{$applicantName}'s application has been approved and is ready for encoding/finalization.";
            } elseif ($this->eventType === 'completed') {
                $title = "Driver Application Completed";
                $message = "{$applicantName}'s driver application has been finalized.";
            } elseif ($this->eventType === 'rejected') {
                $title = "Driver Application Rejected";
                $message = "Your driver application for {$applicantName} has been rejected.";
            } elseif ($this->eventType === 'returned') {
                $title = "Driver Application Returned";
                $message = "Your driver application for {$applicantName} has been returned. Please check the remarks and update.";
            }
        }

        // --- NEW FRANCHISE MESSAGES ---
        if ($type === 'New Franchise') {
            if ($this->eventType === 'created') {
                $title = "New Franchise Application";
                $message = "{$applicantName} has submitted a new franchise application.";
            } elseif ($this->eventType === 'completed_initial') { // <-- ADD THIS BLOCK
                $title = "Franchise Application Completed";
                $message = "{$applicantName} has completed and submitted their initial franchise application.";
            } elseif ($this->eventType === 'inspector_approved') {
                $title = "CAPO Approval Needed";
                $message = "Inspector has approved {$applicantName}'s application. CAPO review is now required.";
            } elseif ($this->eventType === 'ready_for_review') {
                $title = "Application Ready for Review";
                $message = "{$applicantName}'s application is fully evaluated, inspected, and CAPO-approved. Review needed.";
            } elseif ($this->eventType === 'reviewer_approved') {
                $title = "SP Approval Needed";
                $message = "Reviewer has approved {$applicantName}'s application. SP Approver action required.";
            } elseif ($this->eventType === 'sp_approved') {
                $title = "TAB Approval Needed";
                $message = "SP Approver has approved {$applicantName}'s application. TAB Approver action required.";
            } elseif ($this->eventType === 'tab_approved') {
                $title = "Application Ready for Finalization";
                $message = "TAB Approver has approved {$applicantName}'s application. It is ready to be finalized.";
            }
        }

        // --- CHANGE OF OWNER & DECEASED MESSAGES ---
        if (in_array($type, ['Change of Owner', 'Change of Owner (Deceased)'])) {
            if ($this->eventType === 'created') {
                $title = "New {$type} Application";
                $message = "{$franchiseNumber} has submitted a {$type} application.";
            } elseif ($this->eventType === 'completed_initial') { 
                $title = "{$type} Completed";
                $message = "{$franchiseNumber} has completed and submitted their initial {$type} application.";
            } elseif ($this->eventType === 'ready_for_review') {
                $title = "Application Ready for Review";
                $message = "{$franchiseNumber}'s application is fully evaluated and paid. Review needed.";
            } elseif ($this->eventType === 'reviewer_approved') {
                $title = "TAB Approval Needed";
                $message = "Reviewer has approved {$franchiseNumber}'s application. TAB Approver action required.";
            } elseif ($this->eventType === 'tab_approved') {
                $title = "Application Ready for Finalization";
                $message = "TAB Approver has approved {$franchiseNumber}'s application. It is ready to be finalized.";
            } elseif ($this->eventType === 'rejected') {
                $title = "Application Rejected";
                $message = "Your {$type} application for {$franchiseNumber} has been rejected.";
            } elseif ($this->eventType === 'returned') {
                $title = "Application Returned";
                $message = "Your {$type} application for {$franchiseNumber} has been returned. Please check the remarks and update.";
            }
        }

        // --- FRANCHISE OWNER ACCOUNT MESSAGES ---
        if ($type === 'Franchise Owner Account') {
            if ($this->eventType === 'created') {
                $title = "New Account Application";
                $message = "{$applicantName} has applied for a Franchise Owner Account.";
            } elseif ($this->eventType === 'evaluator_approved') {
                $title = "Account Application Approved";
                $message = "Evaluator has approved {$applicantName}'s account application. Admin/Encoder action required.";
            } elseif ($this->eventType === 'rejected') {
                $title = "Application Rejected";
                $message = "Your Franchise Owner Account application has been rejected.";
            } elseif ($this->eventType === 'returned') {
                $title = "Application Returned";
                $message = "Your Franchise Owner Account application has been returned. Please check the remarks.";
            }
        }

        // --- CHANGE OF UNIT MESSAGES ---
        if ($type === 'Change of Unit') {
            if ($this->eventType === 'created') {
                $title = "New Change of Unit Application";
                $message = "{$applicantName} has submitted a Change of Unit application.";
            } elseif ($this->eventType === 'inspector_approved') {
                $title = "CAPO Approval Needed";
                $message = "Inspector has approved {$applicantName}'s Change of Unit. CAPO review required.";
            } elseif ($this->eventType === 'ready_for_review') {
                $title = "Application Ready for Review";
                $message = "{$applicantName}'s Change of Unit is fully evaluated, CAPO-approved, and paid. Review needed.";
            } elseif ($this->eventType === 'reviewer_approved') {
                $title = "TAB Approval Needed";
                $message = "Reviewer has approved {$applicantName}'s Change of Unit. TAB Approver action required.";
            } elseif ($this->eventType === 'tab_approved') {
                $title = "Application Ready for Finalization";
                $message = "TAB Approver has approved {$applicantName}'s Change of Unit. Ready for finalization.";
            } elseif ($this->eventType === 'rejected') {
                $title = "Application Rejected";
                $message = "Your Change of Unit application for {$applicantName} has been rejected.";
            } elseif ($this->eventType === 'returned') {
                $title = "Application Returned";
                $message = "Your Change of Unit application for {$applicantName} has been returned. Please check the remarks.";
            }
        }

        // --- RENEWAL MESSAGES ---
        if ($type === 'Renewal') {
            if ($this->eventType === 'created') {
                $title = "New Renewal Application";
                $message = "{$applicantName} has submitted a Franchise Renewal application.";
            } elseif ($this->eventType === 'auto_generated') { // <-- ADD THIS BLOCK
                $title = "Annual Renewal Required";
                $message = "Your franchise renewal period for {$franchiseNumber} has started. Please complete your application and requirements.";
            } elseif ($this->eventType === 'completed_initial') { 
                $title = "Renewal Completed";
                $message = "{$applicantName} has completed and submitted their initial Renewal application.";
            } elseif ($this->eventType === 'inspector_approved') {
                $title = "CAPO Approval Needed";
                $message = "Inspector has approved {$applicantName}'s Renewal. CAPO review required.";
            } elseif ($this->eventType === 'ready_for_review') {
                $title = "Application Ready for Review";
                $message = "{$applicantName}'s Renewal is fully evaluated, CAPO-approved, and paid. Review needed.";
            } elseif ($this->eventType === 'reviewer_approved') {
                $title = "SP Approval Needed";
                $message = "Reviewer has approved {$applicantName}'s Renewal. SP Approver action required.";
            } elseif ($this->eventType === 'sp_approved') {
                $title = "TAB Approval Needed";
                $message = "SP Approver has approved {$applicantName}'s Renewal. TAB Approver action required.";
            } elseif ($this->eventType === 'tab_approved') {
                $title = "Application Ready for Finalization";
                $message = "TAB Approver has approved {$applicantName}'s Renewal. Ready for finalization.";
            } elseif ($this->eventType === 'rejected') {
                $title = "Application Rejected";
                $message = "Your Renewal application for {$applicantName} has been rejected.";
            } elseif ($this->eventType === 'returned') {
                $title = "Application Returned";
                $message = "Your Renewal application for {$applicantName} has been returned. Please check the remarks.";
            }
        }

        return [
            'application_id' => $this->application->id,
            'reference_number' => $this->application->reference_number,
            'title' => $title,
            'message' => $message,
            'url' => $this->determineUrl($notifiable, $type),
        ];
    }

    private function determineUrl($user, $type)
    {
        $roles = $user->active_roles ?? [];
        $id = $this->application->id;

        // 1. Admin & Encoder (Shared Routes)
        if (in_array('admin', $roles) || in_array('encoder', $roles)) {
            return match($type) {
                'Renewal'                    => route('admin.applications.renewal.show', $id),
                'Change of Unit'             => route('admin.applications.change-of-unit.show', $id),
                'Change of Owner', 
                'Change of Owner (Deceased)' => route('admin.applications.change-of-owner.show', $id),
                'New Franchise'              => route('admin.applications.show-new-franchise', $id),
                'New Driver'                 => route('admin.applications.new-driver.show', $id),
                default                      => route('admin.applications.show', $id),
            };
        }

        // 2. Evaluator
        if (in_array('evaluator', $roles)) {
            return match($type) {
                'Change of Unit'             => route('evaluator.applications.show-change-of-unit', $id),
                'Change of Owner', 
                'Change of Owner (Deceased)' => route('evaluator.applications.show-change-of-owner', $id),
                'New Franchise'              => route('evaluator.applications.show-new-franchise', $id),
                'New Driver'                 => route('evaluator.applications.show-new-driver', $id),
                'Franchise Owner Account'    => route('evaluator.applications.show-franchise-owner-account', $id),
                default                      => route('evaluator.applications.show', $id),
            };
        }

        // 3. Inspector
        if (in_array('inspector', $roles)) {
            return match($type) {
                'Change of Unit'  => route('inspector.applications.show-change-of-unit', $id),
                'New Franchise'   => route('inspector.applications.show-new-franchise', $id),
                default           => route('inspector.applications.show', $id),
            };
        }

        // 4. CAPO
        if (in_array('capo', $roles)) {
            return match($type) {
                'Change of Unit'  => route('capo.applications.show-change-of-unit', $id),
                'New Franchise'   => route('capo.applications.show-new-franchise', $id),
                default           => route('capo.applications.show', $id),
            };
        }

        // 5. Reviewer
        if (in_array('reviewer', $roles)) {
            return match($type) {
                'Renewal'                    => route('applications.showRenewal', $id),
                'Change of Unit'             => route('applications.showChangeOfUnit', $id),
                'Change of Owner', 
                'Change of Owner (Deceased)' => route('applications.showChangeOfOwner', $id),
                'New Franchise'              => route('reviewer.applications.showNewFranchise', $id),
                default                      => route('dashboard'),
            };
        }

        // 6. SP Approver
        if (in_array('sp_approver', $roles)) {
            return match($type) {
                'New Franchise' => route('sp_approver.applications.show-new-franchise', $id),
                default         => route('sp_approver.applications.show', $id),
            };
        }

        // 7. TAB Approver
        if (in_array('tab_approver', $roles)) {
            return match($type) {
                'Renewal'                    => route('applications.showRenewal', $id),
                'Change of Unit'             => route('applications.showChangeOfUnit', $id),
                'Change of Owner', 
                'Change of Owner (Deceased)' => route('applications.showChangeOfOwner', $id),
                'New Franchise'              => route('applications.show-new-franchise', $id),
                default                      => route('dashboard'),
            };
        }

        return route('dashboard');
    }
}