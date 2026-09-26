<?php

namespace AC\app\entities\enums;

enum UserRights: string
{
  case MASTER_ADMIN  = '-100';
  case ADMIN_VS      = '-1';
  case ADMIN_LEVEL_1 = '1';
  case ADMIN_LEVEL_2 = '2';
  case ADMIN_LEVEL_3 = '3';
}
