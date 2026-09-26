<?php

namespace AC\core\system\actions\admin\show_table;

use AC\core\system\helpers\HtmlHelper;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\model\BaseModel;
use AC\core\system\object\entity\html\Form;
use AC\core\system\object\entity\html\Table;


class ShowList
{
  private RowList   $rowList;
  private BaseModel $model;

  public function __construct($model, $rowList)
  {
    $this->model   = $model;
    $this->rowList = $rowList;
  }

  /**
   * @param $params
   * @return $this
   */
  public function setProperties($params): static
  {
    foreach ($params as $param => $valueParam) {
      if (property_exists($this, $param)) {
        $this->{$param} = $valueParam;
      }
    }

    return $this;
  }

  /** Вывести талицу списком
   * @param array $data
   *
   * @return string
   */
  public function getRenderTableList(array $data = []): string
  {

    return useLayout()->render('table/show_list', array_merge($this->getMainParams($data), $this->getAdditionalParameters()));
  }

  /** Собираем основные данные/параметры для построения таблицы
   * @param array $data
   * @return array
   */
  protected function getMainParams(array $data = []): array
  {

    return [
      'h1'                 => $this->getH1Title(),
      'description'        => $this->getDescription(),
      'placeAboveTheTable' => $this->getPlaceAboveTheTable(),
      'tableList'          => $this->getTableListData($data),
      'footer'             => $this->getFooter(),
      'form'               => $this->getForm(),
      'popUpWindow'        => count($data) ? $this->getPopUpWindow() : '',
    ];
  }

  protected function getPopUpWindow(): string
  {

    return '';
  }

  /**
   * @param array $data
   * @return array
   */
  protected function getAdditionalParameters(array $data = []): array
  {
    return [];
  }


  /**
   * @return string
   */
  protected function getH1Title(): string
  {
    return '';
  }

  /**
   * @return object
   */
  protected function getPlaceAboveTheTable(): object
  {
    return ObjectHelper::createObject();
  }

  protected function getFooter(): object
  {
    return ObjectHelper::createObject();
  }

  /**
   * @return string
   */
  protected function getDescription(): string
  {
    return '';
  }

  /**
   * @param array $data
   * @return array{titles: Table, rows: array}
   */
  protected function getTableListData(array $data = []): array
  {
    $titles = $this->getTitlesForTableList();
    return ['titles' => $titles, 'rows' => $this->renderDataForTableList($data, $titles->getFields())];
  }


  /**
   * @return Table
   */
  protected function getTitlesForTableList(): Table
  {
    return HtmlHelper::table();
  }

  /**
   * @param $data
   * @param $titleKeys
   * @return array
   */
  protected function renderDataForTableList($data = [], $titleKeys = []): array
  {
    $outData  = [];
    $itemList = $this->getRowList();
    foreach ($data as $datum) {
      $outData[] = $itemList->getRow($datum, $titleKeys);
    }

    return $outData;
  }

  /**
   * @return Form
   */
  protected function getForm(): Form
  {
    $form = HtmlHelper::form();
    $form->class = null;
    $form->id = null;
    $form->action = $this->getFormAction();

    return $form;
  }

  /**
   * @param string $action
   * @return string
   */
  protected function getFormAction(string $action = ''): string
  {
    return $action;
  }

  protected function getRowList(): RowList
  {
    return $this->rowList;
  }

  /**
   * @return BaseModel
   */
  protected function getModel()
  {
    return $this->model;
  }

  protected function setRowList(RowList $rowList): void
  {
    $this->rowList = $rowList;
  }
}