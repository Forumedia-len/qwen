<?php

namespace AC\core\modules\mailing\entities\traits\fields;

trait HasSubject
{
  public string $subject = '';
  
  public function setSubject(?string $subject): void { $this->subject = $subject ?? ''; }
  
  public function getSubject(): string { return $this->subject ?? ''; }
}