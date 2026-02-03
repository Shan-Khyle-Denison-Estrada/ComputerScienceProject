<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case FRANCHISE_OWNER = 'franchise_owner';
}