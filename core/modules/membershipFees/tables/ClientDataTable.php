<?php

namespace AC\core\modules\membershipFees\tables;

use AC\core\modules\clients\tables\ClientDataTable as ClientDataTableBase;

class ClientDataTable extends ClientDataTableBase
{
  public $typeBlock = 'membershipFees';
}