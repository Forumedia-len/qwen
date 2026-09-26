<?php

namespace AC\core\system\http\url;

use AC\app\config\AppConfig;
use AC\core\system\helpers\FileHelper;


/** URL запроса, включая устройство и пути его интерфейса. */
class Url extends URI
{
  protected $basePath;
  protected $devicePath;
  protected $templatePath;
  protected $suffixName;
  
  protected $baseDevices
                                   = [
      'at'          => 'admin',
      'touchscreen' => 'touch',
      'display'     => 'display',
      'mapi'        => 'mapi',
      'widget'      => 'widget',
    ];
  protected $defaultDeviceTemplate = 'site';
  
  public function __construct($uri = null)
  {
    $uri = $this->setBasePaths($uri);
    
    parent::__construct($uri);
    $this->setDevicePath();
  }
  
  protected function setBasePaths($uri = null)
  {
    /** @var AppConfig $config */
    $config = config('App');
    $this->setURI($config->baseURL);
    if (count($this->segments) > 0) {
      $uri            = str_replace($config->baseURL, '', $uri);
      $uri            = str_replace($this->path, '', $uri);
      $this->basePath = FileHelper::trimFilePath($this->path, '/', false);
      $this->setPath('');
      $this->segments = [];
    }
    
    return $uri;
  }
  
  public function getPath($getDevicePath = true)
  {
    $path = ($getDevicePath ? $this->getDevicePath(true) : '') . $this->path;
    
    return ltrim($path, '/');
  }
  
  public function setDevicePath()
  {
    $this->devicePath   = isset($this->segments[0]) && in_array($this->segments[0], array_keys($this->baseDevices)) ? array_shift($this->segments)
      : '';
    $this->templatePath = isset($this->baseDevices[$this->devicePath]) ? $this->baseDevices[$this->devicePath] : $this->defaultDeviceTemplate;
    $this->suffixName   = isset($this->baseDevices[$this->devicePath]) ? $this->baseDevices[$this->devicePath] : '';
    if ($this->devicePath) {
      $this->path = trim(substr(trim($this->path, '/'), strlen($this->devicePath)), '/');
    }
  }
  
  public function getDevicePath($useTrimSeparator = false, $separator = '/')
  {
    return $this->devicePath ? FileHelper::trimFilePath($this->devicePath, $separator, $useTrimSeparator) : '';
  }
  
  public function getTemplatePath($useTrimSeparator = false, $separator = '/')
  {
    return $this->templatePath ? FileHelper::trimFilePath($this->templatePath, $separator, $useTrimSeparator) : '';
  }

  /**
   * Возвращает устройство и родительские интерфейсы в порядке поиска файлов.
   *
   * @param string|null $template Явное устройство или устройство текущего запроса.
   * @return string[]
   */
  public function getTemplateHierarchy(?string $template = null): array
  {
    $template ??= $this->getTemplatePath();

    return \Service::templates()->getLookupOrder($template);
  }
  
  public function getBasePath($useTrimSeparator = false, $separator = '/')
  {
    return $this->basePath ? FileHelper::trimFilePath($this->basePath, $separator, $useTrimSeparator) : '';
  }
  
  public function getSuffixName()
  {
    return ucfirst($this->suffixName);
  }
  
  /**
   * @param $_url
   *
   * @return string BASE_HREF + device
   */
  public function siteUrl($_url = null)
  {
    $url = \Service::url($_url, false);
    $uri = $this->getScheme() . '://';
    $uri .= $this->getAuthority();
    
    $path = $this->getBasePath(true) . $this->getDevicePath(true) . $url->getPath();
    
    $uri .= substr($uri, -1, 1) !== '/' ? '/' . ltrim($path, '/') : ltrim($path, '/');
    
    if ($_url) {
      if ($url->query) {
        $uri .= '?' . $url->getQuery();
      }
      
      if ($url->fragment) {
        $uri .= '#' . $url->getFragment();
      }
    }
    
    return $uri;
  }
  
  /** Получить Базовый путь
   *
   * @param $_url
   *
   * @return string BASE_HREF
   */
  public function baseUrl($_url = null)
  {
    $url = \Service::url($_url, false);
    
    
    $uri = $this->getScheme() . '://';
    $uri .= $this->getAuthority();
    
    $path = $this->getBasePath(true) . $url->getPath();
    $uri  .= substr($uri, -1, 1) !== '/' ? '/' . ltrim($path, '/') : ltrim($path, '/');
    if ($_url) {
      if ($url->query) {
        $uri .= '?' . $url->getQuery();
      }
      
      if ($url->fragment) {
        $uri .= '#' . $url->getFragment();
      }
    }
    
    return $uri;
  }
  
  public function currentUrl($addPath = [], $addQuery = [], $changeFragment = null)
  {
    $uri  = $this->getScheme() . '://';
    $uri  .= $this->getAuthority();
    $path = $this->getBasePath(true) . $this->getPath();
    $uri  .= substr($uri, -1, 1) !== '/' ? '/' . ltrim($path, '/') : ltrim($path, '/');
    if ($this->query || !empty($addQuery)) {
      $uri .= '?' . http_build_query(array_merge($this->query, $addQuery));
    }
    
    if ($this->fragment || !empty($changeFragment)) {
      $uri .= '#' . $changeFragment ?? $this->getFragment();
    }
    
    return $uri;
  }
  
  public function getDefaultDeviceTemplate()
  {
    return $this->defaultDeviceTemplate;
  }
  
  public function cdnUrl($_url = null)
  {
    $url = \Service::url($_url, false);
    
    $uri = CDN_HREF;
    
    $path = $url->getPath(false);
    $uri  .= substr($uri, -1, 1) !== '/' ? '/' . ltrim($path, '/') : ltrim($path, '/');
    if ($_url) {
      if ($url->query) {
        $uri .= '?' . $url->getQuery();
      }
      
      if ($url->fragment) {
        $uri .= '#' . $url->getFragment();
      }
    }
    
    return $uri;
  }
  
  public function getQueryAsArray(): array
  {
    return $this->query;
  }
  
  public function asString(): string
  {
    return (string)$this;
  }
}
