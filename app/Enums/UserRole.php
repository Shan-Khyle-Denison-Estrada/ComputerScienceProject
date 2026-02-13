<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case FRANCHISE_OWNER = 'franchise_owner';
    case COLLECTOR = 'collector';
    case EVALUATOR = 'evaluator';
    case INSPECTOR = 'inspector';
    case CITY_ANTI_POLLUTION_OFFICER = 'city_anti_pollution_officer';
    case REVIEWER = 'reviewer';
    case SP_APPROVER = 'sp_approver';
    case TAB_APPROVER = 'tab_approver';
    case RELEASER = 'releaser';
    case ENCODER = 'encoder';
}