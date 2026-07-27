<?php

namespace App\Enums;

enum CategorizationRuleSource: string
{
    case Manual = 'manual';
    case AiTrained = 'ai_trained';
}
