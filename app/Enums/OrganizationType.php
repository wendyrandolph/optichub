<?php

namespace App\Enums;

enum OrganizationType: string
{
  case SAAS_TENANT = 'saas_tenant';
  case PROVIDER = 'provider';
}
