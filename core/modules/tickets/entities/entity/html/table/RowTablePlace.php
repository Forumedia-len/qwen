<?php

namespace AC\core\modules\tickets\entities\entity\html\table;

use AC\app\locators\Service;
use AC\core\system\helpers\HtmlHelper;
use AC\core\system\object\entity\html\table\RowTable;

class RowTablePlace extends RowTable
{
  public function generateItems($data = [], $new = true, $classItems = null): RowTablePlace
  {
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => lang('Playground') . ':',
          'class'   => $classItems
        ],
      )
    );
    $areasTitles = Service::engines()->areas->getAreasTitles();
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => $new ? view()->render('default\placeInput', [
            'path_ajax_params' => 'area_id=' . array_key_first($areasTitles)
              . (($time_start = Service::request()->findByAlias('time_start', $data, null, 'post')) ? '&time_start="' . $time_start . '"' : '')
              . (($time_finish = Service::request()->findByAlias('time_finish', $data, 0, 'post')) ? '&time_finish="' . $time_finish . '"' : ''),
            'selectAreas'      => useLayout()->render(
              'select',
              [
                'name'           => 'area_id',
                'values'         => $areasTitles,
                'current'        => Service::request()->findByAlias('area_id', $data, null, 'post'),
                'multiple'       => false,
                'otherAttribute' => ' onchange = "return getAreaTimes(this, load_time)"'
              ],
              'common'
            )
          ])
          : $areasTitles[Service::request()->findByAlias('area_id', $data, null, 'post')],
          'class'   => $classItems
        ]
      )
    );

    return parent::generateItems();
  }
}