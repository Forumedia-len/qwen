<?php

/** This file is part of KCFinder project
  *
  *      @desc Autoload Classes
  *   @package KCFinder
  *   @version 3.12
  *    @author Pavel Tzonkov <sunhater@sunhater.com>
  * @copyright 2010-2014 KCFinder Project
  *   @license http://opensource.org/licenses/GPL-3.0 GPLv3
  *   @license http://opensource.org/licenses/LGPL-3.0 LGPLv3
  *      @link http://kcfinder.sunhater.com
  */

spl_autoload_register(function($path) {
    $path = explode("\\", $path);

    if (count($path) == 1)
        return;

    list($ns, $class) = $path;

    if ($ns == "kcfinder") {

        if ($class == "uploader")
            require KCFINDER_PATH . "core/class/uploader.php";
        elseif ($class == "browser")
            require KCFINDER_PATH . "core/class/browser.php";
        elseif ($class == "minifier")
            require KCFINDER_PATH . "core/class/minifier.php";

        elseif (file_exists(KCFINDER_PATH . "core/types/$class.php"))
            require KCFINDER_PATH . "core/types/$class.php";
        elseif (file_exists(KCFINDER_PATH . "lib/class_$class.php"))
            require KCFINDER_PATH . "lib/class_$class.php";
        elseif (file_exists(KCFINDER_PATH . "lib/helper_$class.php"))
            require KCFINDER_PATH . "lib/helper_$class.php";
    }
});

?>