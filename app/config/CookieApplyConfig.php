<?php

namespace AC\app\config;

use AC\core\system\config\BaseConfig;


/**
 * @property array $need
 * @property array $stat
 * @property array $marketing
 * @property array $other
 * @property array $preferences
 */
class CookieApplyConfig extends BaseConfig
{
  private bool $useNewView                  = true;
  private bool $alwaysUseOtherCookies       = true;
  private bool $useLightVersionOtherCookies = true;

  public function __construct($options = [])
  {
    $options = empty($options) ? $this->getOptions() : $options;
    $this->setOptions($options);
  }

  public function setOptions($options = [])
  {
    foreach ($options as $key => $option) {
      $this->addProperty($key, $option);
    }
  }

  public function getList()
  {
    return $this->getProperties();
  }

  public function checkCookiesApplied(): bool
  {
    return !empty($_COOKIE["user_apply_saver_cookie"]);
  }

  public function useLightVersionOtherCookies(): bool
  {
    return $this->useLightVersionOtherCookies;
  }

  public function checkUseOtherCookies(): bool
  {
    if ($this->checkCookiesApplied() && $this->alwaysUseOtherCookies) {
      foreach (json_decode($_COOKIE["user_apply_saver_cookie"]) as $item) {
        if (isset($item->name) && $item->name == 'other' && $item->value == 'Y') {
          return true;
        }
      }
    }

    return false;
  }

  public function checkNewVersionView(): bool
  {
    return $this->useNewView;
  }

  protected function getOptions(): array
  {
    $arrProv       = explode('/', BASE_HREF);
    $base_provider = $arrProv[2];
    $base_provider = str_replace(array('www.', 'ssl.'), '', $base_provider);

    $listCookies['need'] = [
      'area_id'                 => [
        "name"       => "area_id",
        "provider"   => $base_provider,
        "purpose"    => lang('RequiredVariableForTheService', 'cookie'),
        "expiration" => "Session",
        "type"       => "HTTP"
      ],
      'page'                    => [
        "name"       => 'page',
        "provider"   => $base_provider,
        "purpose"    => lang('RequiredVariableForTheService', 'cookie'),
        "expiration" => "Session",
        "type"       => "HTTP"
      ],
      SESSION_COURT             => [
        "name"       => SESSION_COURT,
        "provider"   => $base_provider,
        "purpose"    => lang('RequiredVariableForTheService', 'cookie'),
        "expiration" => "Session",
        "type"       => "HTTP"
      ],
      'weekDay'                 => [
        "name"       => 'weekDay',
        "provider"   => $base_provider,
        "purpose"    => lang('RequiredVariableForTheService', 'cookie'),
        "expiration" => "Session",
        "type"       => "HTTP"
      ],
      'calendarVisibility'      => [
        "name"       => 'calendarVisibility',
        "provider"   => $base_provider,
        "purpose"    => lang('RequiredVariableForTheService', 'cookie'),
        "expiration" => "Session",
        "type"       => "HTTP"
      ],
      'user_apply_saver_cookie' => [
        "name"       => 'user_apply_saver_cookie',
        "provider"   => $base_provider,
        "purpose"    => lang('UsedToStoreUserAgreementSettingsWithCookiePolicy', 'cookie'),
        "expiration" => "Session",
        "type"       => "HTTP"
      ],
      'PHPSESSID'               => [
        "name"       => 'PHPSESSID',
        "provider"   => $base_provider,
        "purpose"    => lang('MaintainsUserSessionStateAcrossPageRequests', 'cookie'),
        "expiration" => "Session",
        "type"       => "HTTP"
      ],
    ];

    if (defined('MC_ARENA') && MC_ARENA) {
      $listCookies['stat'] = [
        '_dc_gtm_UA-' => [
          "name"       => '_dc_gtm_UA',
          "filter"     => array('_dc_gtm_UA-#', '_dc_gtm'),
          "provider"   => $base_provider,
          "purpose"    => lang('UsedByGoogleTagManagerToControlTheLoadingOfAGoogleAnalyticsScriptTag', 'cookie'),
          "expiration" => "1 day",
          "type"       => "HTTP"
        ],
        '_ga'         => [
          "name"       => '_ga',
          "provider"   => $base_provider,
          "purpose"    => lang('RegistersAUniqueIdThatIsUsedToGenerateStatisticalDataAboutTheUseOfTheVisitorsWebsite', 'cookie'),
          "expiration" => "2 years",
          "type"       => "HTTP"
        ],
        '_gat'        => [
          "name"       => '_gat',
          "provider"   => $base_provider,
          "purpose"    => lang('UsedByGoogleAnalyticsToThrottleRequestRate', 'cookie'),
          "expiration" => "1 day",
          "type"       => "HTTP"
        ],
        'collect'     => [
          "name"       => 'collect',
          "provider"   => 'google-analytics.com',
          "purpose"    => lang('TracksTheVisitorAcrossDevicesAndMarketingChannels', 'cookie'),
          "expiration" => "Session",
          "type"       => "Pixel"
        ],
        '_gid'        => [
          "name"       => '_gid',
          "provider"   => $base_provider,
          "purpose"    => lang('RegistersAUniqueIdThatIsUsedToGenerateStatisticalDataAboutTheUseOfTheVisitorsWebsite', 'cookie'),
          "expiration" => "1 day",
          "type"       => "HTTP"
        ],
      ];

      $listCookies['marketing'] = [
        '_fbp'             => [
          "name"       => "_fbp",
          "provider"   => $base_provider,
          "purpose"    => lang('UsedByFacebookToDeliverARangeOfAdvertisingProducts', 'cookie'),
          "expiration" => "3 months",
          "type"       => "HTTP"
        ],
        'ads/ga-audiences' => [
          "name"       => "ads/ga-audiences",
          "provider"   => 'google.com',
          "purpose"    => lang('UsedByGoogleAdWordsToConvertVisitors', 'cookie'),
          "expiration" => "Session",
          "type"       => "Pixel"
        ],
        'fr'               => [
          "name"       => "fr",
          "provider"   => 'facebook.com',
          "purpose"    => lang('UsedByFacebookToDeliverARangeOfAdvertisingProducts', 'cookie'),
          "expiration" => "3 months",
          "type"       => "HTTP"
        ],
        'tr'               => [
          "name"       => "tr",
          "provider"   => 'facebook.com',
          "purpose"    => lang('UsedByFacebookToDeliverARangeOfAdvertisingProducts', 'cookie'),
          "expiration" => "Session",
          "type"       => "Pixel"
        ],
      ];
    } else {
      $listCookies['stat']      = [];
      $listCookies['marketing'] = [];
    }
    $listCookies['preferences'] = [];
    $listCookies['other']       = [];
    if ($this->useLightVersionOtherCookies) {
      $listCookies['other']       = array_merge($listCookies['stat'], $listCookies['marketing'], $listCookies['preferences']);
      $listCookies['stat']        = [];
      $listCookies['marketing']   = [];
      $listCookies['preferences'] = [];
    }

    return $listCookies;
  }
}