<?php

namespace AC\core\modules\tickets\actions\admin\form_content;

use AC\app\helpers\LayoutHelper;
use AC\core\system\actions\admin\contentBlock\ShowAsTable;
use AC\core\system\helpers\HtmlHelper;
use AC\core\system\helpers\TimeHelper;
use Service;

class ShowAsTableTickets extends ShowAsTable
{
  public bool  $new      = true;
  public array $postData = [];

  protected function processContent()
  {
    $this->postData = Service::request()->_post();
    $this->title->addContent($this->new
      ? lang('Insert subscription', 'tickets')
      : lang('Change subscription data', 'tickets'));
    $this->description = HtmlHelper::tag('p')->addContent(!$this->new
      ? lang('the text of the subscription note', 'tickets')
      : '')->asString();

    $table = $this->table();
    $form  = $this->form();

    $form->method = 'post';
    $form->setName('insertTicket');
    $form->addField('ticket_id', ['type' => 'hidden', 'value' => $post['ticket_id'] ?? '']);
    $form
      ->addButton('submit',
        ['type' => 'submit', 'class' => 'button', 'value' => ($this->new ? lang('button_create') : lang('button_update'))])
      ->addButton('reset', ['type' => 'reset', 'class' => 'button', 'value' => lang('button_reset')]);

    $table->style = 'margin: 0 auto;border:none;cell-spacing:0;cell-content:3;';
    $table->class = 'main wide';
    $table->addField('name', ['parent' => lang('Abo data', 'tickets')]);
    $table->addField('value', ['parent' => lang('Abo data', 'tickets')]);
  }
}