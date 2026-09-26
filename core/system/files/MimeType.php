<?php

namespace AC\core\system\files;

/**
 * Class MimeType
 *
 */
class MimeType
{
  use ExtensionsTrait;

  private static $mimes;

  /**
   * Check whether mime-type is supported
   *
   * @param string $mime
   *
   * @return bool
   */
  public static function isSupported($mime)
  {
    self::instanceMimes();
    $delimPos = strpos($mime, "/");
    if (!$delimPos) {
      return false;
    }
    $group = substr($mime, 0, $delimPos);
    $type  = substr($mime, $delimPos + 1);

    return isset(self::$mimes[$group]) && isset(self::$mimes[$group][$type]);
  }

  private static function instanceMimes($new = false)
  {
    if (!self::$mimes || $new) {
      foreach (self::$extensions as $extension => $mime) {
        $delimPos                     = strpos($mime, "/");
        $group                        = substr($mime, 0, $delimPos);
        $type                         = substr($mime, $delimPos + 1);
        self::$mimes[$group][$type][] = $extension;
      }
    }
  }

  /**
   * Return the mime-type information.
   *
   * @param string $mime
   *
   * @return string[] | null
   */
  public static function getExtensions($mime)
  {
    self::instanceMimes();
    $delimPos = strpos($mime, "/");
    if (!$delimPos) {
      return null;
    }
    $group = substr($mime, 0, $delimPos);
    $type  = substr($mime, $delimPos + 1);

    if (empty(self::$mimes[$group])) {
      return null;
    }

    return self::$mimes[$group][$type] ?: null;
  }

  /**
   * Return the plain list of supported mime-types
   *
   * @param string $group Mime group - the one going before the slash
   *
   * @return string[]
   */
  public static function getSupportedMimes($group = null)
  {
    self::instanceMimes();
    $result = [];

    if ($group === null) {
      foreach (self::$mimes as $group => $mimes) {
        foreach ($mimes as $type => $data) {
          $result[] = $group . "/" . $type;
        }
      }
    } elseif (isset(self::$mimes[$group])) {
      foreach (self::$mimes[$group] as $type => $data) {
        $result[] = $group . "/" . $type;
      }
    }

    return $result;
  }

  /**
   * Check whether file extension is supported
   *
   * @param string $extension
   *
   * @return bool
   */
  public static function isSupportedExtension($extension)
  {
    return (bool)(self::$extensions[$extension] ?: false);
  }

  /**
   * Return known relative mime-types.<br/>
   * <b>NOTE: there can be more than one mime-type </b>
   *
   * @param string $extension
   *
   * @return string|null
   */
  public static function getExtensionMimes($extension)
  {
    return self::$extensions[$extension] ?: null;
  }

  /**
   * Return the plain list of supported file extensions.
   *
   * @return string[]
   */
  public static function getSupportedExtensions()
  {
    return array_keys(self::$extensions);
  }
}
