<?php

use AC\app\config\WebIOStateConfig;
use AC\core\engines\Engines;
use AC\core\modules\webIo\models\WebIoOutputsModel;
use AC\core\modules\webIo\models\WebIoTypeSatesModel;
use AC\core\system\helpers\ColorsHelper;

$_page['key'] = 'webIo_monitor';

class lightMonitor
{

  /**
   * @var WebIOStateConfig
   */
  public $lc;
  /**
   * @var Engines
   */
  public $r;
  public $outputs;
  public $types;
  public $reload_time;
  public $area_data;

  function __construct($reload_time = 30000)
  {
    $this->lc      = WebIOStateConfig::instance();
    $this->outputs = $this->lc->getOutputs();
    $this->types   = WebIoTypeSatesModel::getTypeSates('id', true, false, true);

    $this->reload_time = $reload_time;
    $this->r           = new Engines();
    $this->r->areas->getAllAreasData($this->area_data);
  }

  function load()
  {
    $out   = '';
    $check = [];
    if ($this->area_data !== null) {
      $tmp_out = array('obj' => '', 'load' => '');
      foreach ($this->area_data as $area) {
        /** @var $type WebIoTypeSatesModel */
        foreach ($this->types as $type_id => $type) {
          if ($type->active && $type->use_reservation && $area[$type->alias . '_on'] == 1 && isset($this->outputs[$area['area_id'] . '_' . $type_id])) {
            /** @var $output WebIoOutputsModel */
            $output    = $this->outputs[$area['area_id'] . '_' . $type_id];
            $nameAlias = $type->alias . "_" . $output->webio_id . "_" . $output->port;
            $webIo     = $this->lc->webIo[$output->webio_id];
            if ($webIo->ip && $webIo->port) {
              $tmp_out['obj']  .= $nameAlias . " = new lightMonitor('" . $nameAlias . "', 'ajax_light_monitor.php', false, " . $output->port . ", '" . $type->alias . "', '" . $type->alias . "', 'switch', 'loader', '" . $output->webio_id . "');\n";
              $tmp_out['load'] .= $nameAlias . ".checkStatus();\n";
            }
          }
        }
      }
      $out .= '<script>' . "\n";
      $out .= $tmp_out['obj'] . "\n";

      $out .= 'if (typeof document.attachEvent!=\'undefined\')' . "\n";
      $out .= 'window.attachEvent(\'onload\',loadAjax);' . "\n";
      $out .= 'else' . "\n";
      $out .= 'window.addEventListener(\'load\',loadAjax, false);' . "\n";

      $out .= 'function loadAjax(){' . "\n";
      $out .= $tmp_out['load'] . "\n";
      $out .= 'window.setTimeout("loadAjax()", ' . $this->reload_time . ');' . "\n";
      $out .= '}' . "\n";
      $out .= '</script>' . "\n";
    }
    unset($tmp_out);

    return $out;
  }

  function view()
  {
    $out = '';
    if ($this->area_data !== null) {
      $out     .= $this->load();
      $tmp_out = array('title' => '', 'lamp' => '', 'switch' => '', 'loader' => '');
      $i       = count($this->area_data);

      array_push($this->area_data, '');
      $type_id = null;
      $sports  = [];
      foreach ($this->area_data as $area) {
        if (!empty($area)) {
          $sports[$area['sport_title']]['count'] ??= 0;
          $sports[$area['sport_title']]['count']++;
          $sports[$area['sport_title']]['id'] ??= $area['sport_id'];
          $sports[$area['sport_title']]['type_color'] ??= $area['type_color'];
        }
        if (isset($type_id) && ((isset($area['type_id']) && $type_id != $area['type_id']) || ($i == 0))) {
          foreach ($sports as $sport_title => $sport) {
            $tmp_out['sport_title'] .= '<th colspan="' . $sport['count'] . '" style="color:#fff;background-color:#' . ColorsHelper::hexByTypeSport(
                $type_id,
                $sport['id']
              ) . '">' . $sport_title . '</th>' . "\n";
          }
          $out .= '<table>' . "\n";
          $out .= '<tr>' . $tmp_out['sport_title'] . '</tr>' . "\n";
          $out .= '<tr>' . $tmp_out['title'] . '</tr>' . "\n";
          $out .= '<tr>' . $tmp_out['lamp'] . '</tr>' . "\n";
          $out .= '<tr>' . $tmp_out['switch'] . '</tr>' . "\n";
          $out .= '<tr>' . $tmp_out['loader'] . '</tr>' . "\n";
          $out .= '</table>' . "\n";
          $out .= '</div><br/>' . "\n";
          unset($tmp_out);
          $type_id = null;
          $sports  = [];
          if ($i == 0) {
            break;
          }
        }

        if (!isset($type_id) || $type_id != $area['type_id']) {
          $out .= '<div class="placeType">' . "\n";
          $out .= '<div class="titleAreaType" style="background-color:#' . $area['type_color'] . '">' . $area['type_title'] . '</div>' . "\n";
        }

        $tmp_out['title'] .= '<th colspan="' . count($this->types) . '">' . $area['title'] . '</th>' . "\n";

        /** @var $type WebIoTypeSatesModel */
        foreach ($this->types as $type_id => $type) {
          if ($type->active && $type->use_reservation && $area[$type->alias . '_on'] == 1 && isset($this->outputs[$area['area_id'] . '_' . $type_id])) {
            /** @var $output WebIoOutputsModel */
            $output    = $this->outputs[$area['area_id'] . '_' . $type_id];
            $nameAlias = $type->alias . "_" . $output->webio_id . "_" . $output->port;

            $tmp_out['lamp'] .= '<td><img src="' . base_url(
                paths()->getAssetsDir('images/lights/' . $type->alias . '_off.jpg')
              ) . '" alt="" id="' . $nameAlias . '"/></td>' . "\n";

            $tmp_out['switch'] .= '<td>' . "\n";
            $tmp_out['switch'] .= '<img src="' . base_url(
                paths()->getAssetsDir('images/lights/switch_on.gif')
              ) . '" alt="" id="switch_' . $output->webio_id . "_" . $output->port . '" onclick="' . $nameAlias . '.manageLight(\'ON\')" class="switchButton" />' . "\n";
            $tmp_out['switch'] .= '<img src="' . base_url(
                paths()->getAssetsDir('images/lights/switch_off.gif')
              ) . '" alt="" onclick="' . $nameAlias . '.manageLight(\'OFF\')" class="switchButton" />' . "\n";
            $tmp_out['switch'] .= '</td>' . "\n";

            $tmp_out['loader'] .= '<td class="loaderLine">' . "\n";
            $tmp_out['loader'] .= '<div class="loader" id="loader_' . $output->webio_id . "_" . $output->port . '"><img src="' . base_url(
                paths()->getAssetsDir('images/lights/ajax-loader.gif')
              ) . '" alt=""/></div>' . "\n";
            $tmp_out['loader'] .= '</td>' . "\n";
          } else {
            $tmp_out['lamp'] .= '<td></td>' . "\n";

            $tmp_out['switch'] .= '<td></td>' . "\n";

            $tmp_out['loader'] .= '<td class="loaderLine"></td>' . "\n";
          }
        }

        $type_id = $area['type_id'];
        $i--;
      }
    }
    unset($tmp_out);

    return $out;
  }
}


$lm = new lightMonitor();

$_page['content'][] = $lm->view();
$_page['css'][]     = 'light_monitor';
$_page['js'][]      = 'light_monitor';
ob_end_clean();
