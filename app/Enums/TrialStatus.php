<?php

namespace App\Enums;

enum TrialStatus: string
{
  case ACTIVE = 'active';
  case GRACE = 'grace';
  case EXPIRED = 'expired';
  case CONVERTED = 'converted';
}
