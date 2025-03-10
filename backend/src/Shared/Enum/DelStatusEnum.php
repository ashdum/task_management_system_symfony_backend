<?php
namespace App\Shared\Enum;

enum DelStatusEnum: int
{
    case ACTIVE = 1;  // Active record
    case DELETED = 2; // Deleted record
}