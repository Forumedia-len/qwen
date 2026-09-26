<?php

namespace AC\core\modules\mailing\entities\traits\fields;

use AC\core\modules\mailing\entities\enums\ModeTemplate;

trait HasModeTemplate
{
  public ModeTemplate $mode;
  
  public function getMode(): int { return $this->mode->value; }
  
  public function setMode(?int $mode): void { $this->mode = ModeTemplate::from($mode); }
  
  public function getModeTemplate(): ModeTemplate { return $this->mode; }
}