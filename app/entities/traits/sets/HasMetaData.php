<?php

namespace AC\app\entities\traits\sets;

use AC\app\entities\traits\fields\HasEntryId;
use AC\app\entities\traits\fields\HasId;
use AC\app\entities\traits\fields\HasName;
use AC\app\entities\traits\fields\HasTypeBlock;
use AC\app\entities\traits\fields\HasValue;

trait HasMetaData
{
  use HasId, HasTypeBlock, HasEntryId, HasName, HasValue;
}