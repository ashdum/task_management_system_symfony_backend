<?php
// backend/src/Enum/DelStatusEnum.php
namespace App\Enum;

enum DelStatusEnum: int
{
    case ACTIVE = 1;  // Активная запись
    case DELETED = 2; // Удалённая запись
}