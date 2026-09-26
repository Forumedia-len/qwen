<?php

namespace AC\core\system\actions\admin\contentBlock;


use AC\core\system\actions\Show;
use AC\core\system\helpers\HtmlHelper;
use AC\core\system\object\entity\html\Form;
use AC\core\system\object\entity\html\Table;
use AC\core\system\object\entity\html\TagHtml;

class ShowAsTable extends Show
{
  public TagHtml $title;
  public ?string $description;
  public ?string $header;
  public ?string $footer;

  protected Form  $form;
  protected Table $table;

  protected function instance(): void
  {
    parent::instance();

    $this->title = $this->instanceTitle(HtmlHelper::tag('h1'));
    $this->form  = $this->instanceForm(HtmlHelper::form());
    $this->table = $this->instanceTable(HtmlHelper::table());
    $this->processContent();
  }

  protected function processContent()
  {
  }

  public function instanceTitle(TagHtml $title): TagHtml
  {
    $this->title = $title;

    return $this->title;
  }

  public function instanceForm(Form $form): Form
  {
    $this->form = $form;

    return $this->form;
  }

  public function instanceTable(Table $table): Table
  {
    $this->table = $table;

    return $this->table;
  }

  public function asString(): string
  {
    return useLayout()->render('show/asTable', ['block' => $this]);
  }

  /**
   * @return string
   */
  public function getTitle(): string
  {

    return $this->title->asString();
  }

  /**
   * @return string
   */
  public function getDescription(): string
  {
    return $this->description ?? '';
  }


  public function getContent(): string
  {
    return $this->form()->addContent($this->table()->asString())->asString();
  }

  public function getHeader(): string
  {
    return '';
  }

  public function getFooter(): string
  {
    return $this->footer ?? '';
  }

  /**
   * @return Table
   */
  public function table(): Table
  {
    return $this->table;
  }


  /**
   * @return Form
   */
  public function form(): Form
  {
    return $this->form;
  }

}