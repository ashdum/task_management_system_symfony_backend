<?php
// backend/src/Enum/RoleEnum.php
namespace App\Enum;

enum RoleEnum: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
}