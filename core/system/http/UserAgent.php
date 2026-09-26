<?php

namespace AC\core\system\http;


use AC\app\config\UserAgentConfig;

/**
 * Abstraction for an HTTP user agent
 */
class UserAgent
{
  /**
   * Current user-agent
   *
   * @var string
   */
  protected $agent = '';

  /**
   * Flag for if the user-agent belongs to a browser
   *
   * @var bool
   */
  protected $isBrowser = false;

  /**
   * Flag for if the user-agent is a robot
   *
   * @var bool
   */
  protected $isRobot = false;

  /**
   * Flag for if the user-agent is a mobile browser
   *
   * @var bool
   */
  protected $isMobile = false;

  /**
   * Holds the config file contents.
   *
   * @var \AC\app\config\UserAgentConfig
   */
  protected $config;

  /**
   * Current user-agent platform
   *
   * @var string
   */
  protected $platform = '';

  /**
   * Current user-agent browser
   *
   * @var string
   */
  protected $browser = '';

  /**
   * Current user-agent version
   *
   * @var string
   */
  protected $version = '';

  /**
   * Current user-agent mobile name
   *
   * @var string
   */
  protected $mobile = '';

  /**
   * Current user-agent robot name
   *
   * @var string
   */
  protected $robot = '';

  /**
   * HTTP Referer
   *
   * @var mixed
   */
  protected $referrer;

  /**
   * Constructor
   *
   * Sets the User Agent and runs the compilation routine
   */
  public function __construct(?UserAgentConfig $config = null)
  {
    $this->config = $config ? : config('UserAgent');

    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $this->agent = trim($_SERVER['HTTP_USER_AGENT']);
      $this->compileData();
    }
  }

  /**
   * Is Browser
   *
   * @param string $key
   */
  public function isBrowser($key = null)
  {
    if (!$this->isBrowser) {
      return false;
    }

    // No need to be specific, it's a browser
    if ($key === null) {
      return true;
    }

    // Check for a specific browser
    return isset($this->config->browsers[$key]) && $this->browser === $this->config->browsers[$key];
  }

  /**
   * Is Robot
   *
   * @param string $key
   */
  public function isRobot($key = null)
  {
    if (!$this->isRobot) {
      return false;
    }

    // No need to be specific, it's a robot
    if ($key === null) {
      return true;
    }

    // Check for a specific robot
    return isset($this->config->robots[$key]) && $this->robot === $this->config->robots[$key];
  }

  /**
   * Is Mobile
   *
   * @param string $key
   */
  public function isMobile($key = null)
  {
    if (!$this->isMobile) {
      return false;
    }

    // No need to be specific, it's a mobile
    if ($key === null) {
      return true;
    }

    // Check for a specific robot
    return isset($this->config->mobiles[$key]) && $this->mobile === $this->config->mobiles[$key];
  }

  /**
   * Is this a referral from another site?
   */
  public function isReferral()
  {
    if (!isset($this->referrer)) {
      if (empty($_SERVER['HTTP_REFERER'])) {
        $this->referrer = false;
      } else {
        $refererHost = @parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $ownHost     = parse_url(\base_url(), PHP_URL_HOST);

        $this->referrer = ($refererHost && $refererHost !== $ownHost);
      }
    }

    return $this->referrer;
  }

  /**
   * Agent String
   */
  public function getAgentString()
  {
    return $this->agent;
  }

  /**
   * Get Platform
   */
  public function getPlatform()
  {
    return $this->platform;
  }

  /**
   * Get Browser Name
   */
  public function getBrowser()
  {
    return $this->browser;
  }

  /**
   * Get the Browser Version
   */
  public function getVersion()
  {
    return $this->version;
  }

  /**
   * Get The Robot Name
   */
  public function getRobot()
  {
    return $this->robot;
  }

  /**
   * Get the Mobile Device
   */
  public function getMobile()
  {
    return $this->mobile;
  }

  /**
   * Get the referrer
   */
  public function getReferrer()
  {
    return empty($_SERVER['HTTP_REFERER']) ? '' : trim($_SERVER['HTTP_REFERER']);
  }

  /**
   * Parse a custom user-agent string
   */
  public function parse($string)
  {
    // Reset values
    $this->isBrowser = false;
    $this->isRobot   = false;
    $this->isMobile  = false;
    $this->browser   = '';
    $this->version   = '';
    $this->mobile    = '';
    $this->robot     = '';

    // Set the new user-agent string and parse it, unless empty
    $this->agent = $string;

    if (!empty($string)) {
      $this->compileData();
    }
  }

  /**
   * Compile the User Agent Data
   */
  protected function compileData()
  {
    $this->setPlatform();

    foreach (['setRobot', 'setBrowser', 'setMobile'] as $function) {
      if ($this->{$function}() === true) {
        break;
      }
    }
  }

  /**
   * Set the Platform
   */
  protected function setPlatform()
  {
    if (is_array($this->config->platforms) && $this->config->platforms) {
      foreach ($this->config->platforms as $key => $val) {
        if (preg_match('|' . preg_quote($key, '|') . '|i', $this->agent)) {
          $this->platform = $val;

          return true;
        }
      }
    }

    $this->platform = 'Unknown Platform';

    return false;
  }

  /**
   * Set the Browser
   */
  protected function setBrowser()
  {
    if (is_array($this->config->browsers) && $this->config->browsers) {
      foreach ($this->config->browsers as $key => $val) {
        if (preg_match('|' . $key . '.*?([0-9\.]+)|i', $this->agent, $match)) {
          $this->isBrowser = true;
          $this->version   = $match[1];
          $this->browser   = $val;
          $this->setMobile();

          return true;
        }
      }
    }

    return false;
  }

  /**
   * Set the Robot
   */
  protected function setRobot()
  {
    if (is_array($this->config->robots) && $this->config->robots) {
      foreach ($this->config->robots as $key => $val) {
        if (preg_match('|' . preg_quote($key, '|') . '|i', $this->agent)) {
          $this->isRobot = true;
          $this->robot   = $val;
          $this->setMobile();

          return true;
        }
      }
    }

    return false;
  }

  /**
   * Set the Mobile Device
   */
  protected function setMobile()
  {
    if (is_array($this->config->mobiles) && $this->config->mobiles) {
      foreach ($this->config->mobiles as $key => $val) {
        if (false !== (stripos($this->agent, $key))) {
          $this->isMobile = true;
          $this->mobile   = $val;

          return true;
        }
      }
    }

    return false;
  }

  /**
   * Outputs the original Agent String when cast as a string.
   */
  public function __toString()
  {
    return $this->getAgentString();
  }
}
