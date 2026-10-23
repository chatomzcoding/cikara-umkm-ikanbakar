<?php

namespace App\Enums;

enum NotificationCategory: string
{
    case Alert = 'alert';
    case Event = 'event';
    case Activity = 'activity';
}
