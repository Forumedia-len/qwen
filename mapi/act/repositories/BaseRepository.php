<?php

namespace AC\mapi\act\repositories;

use AC\core\system\db\DB;
use AC\core\system\db\GenerateQueryDB;
use AC\core\system\helpers\JsonHelper;

class BaseRepository
{
  protected DB $db;
  protected DB $baseDB;
  protected GenerateQueryDB $gQDBS;
  protected array $query = [];
  protected array $dataAs = [];

  public function __construct(array $config = [])
  {
    $this->db     = DB::instance('site', $config['new'] ?? false, $config);
    $this->baseDB = DB::instance('base');
    $this->gQDBS  = new GenerateQueryDB($this->db);
    if (!isset($config['process']) || $config['process']) {
      $this->process();
    }
  }

  protected function process(): void
  {

  }

  public function query(&$query = []): void
  {
    $query = array_merge($query, $this->query);
  }

  public function run($debug = true): void
  {
    if (!empty($this->query)) {
      $this->db->executingAllQuery($this->query, $debug);
    }
  }

  public function view(): void
  {
    if (!empty($this->query)) {
      Debug()::dE($this->query);
    }
  }

  public function asCsv($action = 'view'): void
  {
    if (!empty($this->dataAs)) {
      $csv = "\xEF\xBB\xBF" . implode(';', array_keys(current($this->dataAs))) . "\n";
      foreach ($this->dataAs as $client) {
        $csv .= implode(';', $client) . "\n";
      }
      if ($action == 'save') {
        Debug()::saveF($csv, ROOT_PATH . 'logs/', 'export.csv');
      } elseif ($action == 'file') {
        header('Content-Disposition: attachment; filename=export.csv');
        header("Content-Type: application/x-force-download; name=\"export.csv\"; charset=utf-8");
        echo $csv;
      } else {
        echo('<pre>' . $csv . '</pre>');
      }
    } else {
      echo('Нет данных');
    }
  }

  public function asJson($action = 'view'): void
  {
    if (!empty($this->dataAs)) {
      if ($action == 'save') {
        Debug()::logAsJson($this->dataAs);
      } elseif ($action == 'file') {
        header('Content-Disposition: attachment; filename=export.json');
        header("Content-Type: application/x-force-download; name=\"export.json\"; charset=utf-8");
        echo "\xEF\xBB\xBF" . JsonHelper::encode($this->dataAs);
      } else {
        header('Content-Type: application/json; charset=utf-8');
        echo JsonHelper::encode($this->dataAs);
      }
    } else {
      echo('Нет данных');
    }
  }
}