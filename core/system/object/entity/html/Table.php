<?php

namespace AC\core\system\object\entity\html;

use AC\core\system\helpers\HtmlHelper;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\object\entity\html\table\FieldTable;
use AC\core\system\object\entity\html\table\RowTable;
use AC\core\system\object\entity\html\table\TdField;

class Table extends TagHtml
{
  private int $countRows = 1;
  private int $countCols = 0;

  /**
   * @var FieldTable[]
   */
  private array $fields = [];
  /**
   * @var RowTable[]
   */
  private array $rows = [];

  public function addField($name, $properties = []): Table
  {
    /** @var FieldTable $field */
    $field = ObjectHelper::createEntity('html\table\FieldTable', null, $name, $properties);
    if (empty($field->parent)) {
      $field->row = $this->countRows;
    }
    $this->countCols += $field->col;

    $this->fields[] = $field;

    return $this;
  }

  /**
   * @param $row
   * @return $this
   */
  public function addRow($row): Table
  {
    if(is_array($row)) {
      $tableRow = HtmlHelper::tagTr();
      foreach ($row as $item) {
        if(is_array($item)) {
          $item = $this->getTdThTag($item);
        }
        $tableRow->addItem($item);
      }
      $this->rows[] = $tableRow;
    } elseif($row instanceof RowTable) {
      $this->rows[] = $row;
    }

    return $this;
  }

  public function getTdThTag($properties = [], $tag = 'td'): TdField
  {
    return HtmlHelper::tagTdTh($tag, null, null, $properties);
  }

  /**
   * @return array
   */
  public function getFields(): array
  {
    return $this->fields;
  }

  /**
   * @return array[TagHtml[]]
   */
  public function getRowsTitles(): array
  {
    $rows = [];

    for ($row = 1; $row <= $this->countRows; $row++) {
      /** @var object{'title': ?string, 'col': int} $col */
      $col = (object)['title' => null, 'col' => 0];
      foreach ($this->fields as $fieldTable) {
        $title = $fieldTable->row != $row || $this->countRows == 1 ? $fieldTable->title : ($fieldTable->parent ?: '');
        if (($col->col > 1 && $col->title != ($title ?: $fieldTable->title))) {
          $rows[$row][] = $this->getTdThTag(['colspan' => $col->col, 'content' => $col->title], 'th');
          $col          = (object)['title' => null, 'col' => 0];
        }
        if ($fieldTable->row == $row) {
          if ($fieldTable->parent && ($col->title == null || $col->title == $fieldTable->parent)) {
            $col->col   += $fieldTable->col;
            $col->title = $fieldTable->parent;
            continue;
          }
        }
        $rowspan = ($row != $fieldTable->row && $fieldTable->row > 1 ? $fieldTable->row : null);
        if ($title) {
          $rows[$row][] = $this->getTdThTag(['content' => $title, 'rowspan' => $rowspan], 'th');
        }
      }
      if ($col->col > 1) {
        $rows[$row][] = $this->getTdThTag(['content' => $col->title, 'colspan' => $col->col], 'th');
      }
    }

    return $rows;
  }

  public function getRows(): array
  {
    return $this->rows;
  }

  public function getCountCols(): int
  {
    return $this->countCols;
  }

  public function setCountRows(int $countRows): void
  {
    $this->countRows = $countRows;
  }

  public function asString(): string
  {
    return useLayout()->render('html\table', ['table' => $this], 'common');
  }
}