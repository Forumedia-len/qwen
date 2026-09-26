<?php

namespace AC\app\config;

use AC\core\modules\config\models\ConfigModel;
use Service;
use AC\core\system\config\BaseConfig;

class CombinedSitesConfig extends BaseConfig
{

  /** Период жизни данных в секундах
   * @var int
   */
  protected int $linkLifetime = 60*60*24*30;

  /**
   *
   */
  public function __construct()
  {
    $this->setProperty('addressOfTheCombinedSites', Service::configDB('common', 'address_of_the_combined_sites'));
    $combinedSitesData = Service::configDB('common', 'combined_sites_data');
    $this->setProperty('combinedSitesData', (!empty($combinedSitesData) && is_string($combinedSitesData) ? json_decode($combinedSitesData, true) : []));
  }

  /**
   * @return bool
   */
  public function checkUseCombinedSites(): bool
  {
    return $this->issetProperty('addressOfTheCombinedSites', true);
  }

  /**
   * @param string $device
   * @param string $key
   *
   * @return array
   */
  public function geStructureBookingLinks(string $device = 'site', string $key = 'reservations'): array
  {
    $outData = [];
    if ($this->checkUseCombinedSites()) {
      $array = Service::engines()->areas->selectActiveType('type_id');
      $i     = end($array)->type_id + 1;

      foreach ($this->getCombinedSitesData($device) as $item) {
        $outData[$key . '_' . $i] = $item;
        $i++;
      }
    }

    return $outData;
  }


  /**
   * @param $device
   *
   * @return array
   */
  protected function getCombinedSitesData($device): array
  {
    $outData = [];
    if ($this->checkUseCombinedSites()) {
      $outData = $this->setCombinedSitesData($device);
    }

    return $outData;
  }

  /**
   * @param $device
   *
   * @return mixed
   */
  protected function setCombinedSitesData($device): mixed
  {
    if ($this->checkUseCombinedSites()) {
      $combinedSitesData = $this->getProperty('combinedSitesData') ?? [];
      if (empty($combinedSitesData[$device]['data']) || !$this->checkTimeRequestDataCombinedSites($combinedSitesData[$device]['dateRequest'])) {
        $combinedSitesData = array_replace_recursive($combinedSitesData,
          [$device => ['dateRequest' => date('Y-m-d H:i:s'), 'data' => $this->requestDataCombinedSites($device)]]);
        $this->setProperty('combinedSitesData', $combinedSitesData);
        $this->insertCombinedSitesData($combinedSitesData);
      }
    }

    return $this->getProperty('combinedSitesData')[$device]['data'] ?? [];
  }

  protected function checkTimeRequestDataCombinedSites($dateRequest): bool
  {
    if(strtotime('now') - strtotime($dateRequest) < $this->linkLifetime) {
      return true;
    }
    return false;
  }

  protected function insertCombinedSitesData($value)
  {
    (new ConfigModel())->getEngine()->saveItem('combined_sites_data', json_encode($value, JSON_UNESCAPED_UNICODE));
  }

  /**
   * @param $device
   *
   * @return array
   */
  protected function requestDataCombinedSites($device): array
  {
    if ($device && $this->checkUseCombinedSites()) {
      $url = $this->getProperty('addressOfTheCombinedSites') . 'mapi/api.php?action=bookingLinks&device=' . $device;

      return json_decode(file_get_contents($url), true);
    }

    return [];
  }
}