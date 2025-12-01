<?php

namespace App\Entity;

enum ProgressStatus: string
{
    case NEW = 'new';
    case LEARNING = 'learning';
    case MASTERED = 'mastered';
}
