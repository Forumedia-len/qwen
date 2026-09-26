<?php
/**
 * @since             22.01.14 21:49
 * @package           delphinpro/dumper
 * @author            delphinpro delphinpro@gmail.com
 * @copyright         copyright (C) 2014-2015 delphinpro
 * @license           Licensed under the MIT license
 */

namespace AC\core\system\debug\dumper;

/**
 * Class Dumper
 *
 * @package delphinpro\dumper
 */
class Dumper
{

    const EXPAND = true;

    public static $ROOT = '';

    private static $log = false;
    private static $id = 1;

    public static function pre($var, $title = '', $exit = false)
    {
        self::_renderStyles();

        if (is_int($title)) {
            $exit  = $title;
            $title = '';
        }

        echo '<div class="php_debug_message_box">' . PHP_EOL;
        if ($title != '') {
            echo '<div class="php_debug_message_box_title">' . $title . '</div>';
        }
        echo '<div class="php_debug_message_box_inner">';
        if (empty( $var ) || is_bool($var)) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        } else {
            echo '<pre>', htmlspecialchars(print_r($var, true)), '</pre>';
        }
        echo '</div>';
        echo self::printDebugBacktrace(debug_backtrace(false));
        echo '</div>';

        if ($exit === 1) {
            die();
        }
    }

    public static function printDebugBacktrace($trace)
    {
        static $index = 0;

        echo '<code style="color: #aaa;font-size: 0.85em;padding-left: 5px;">' . $trace[0]['file'] . ':' .
            $trace[0]['line'] .
            '</code>';
        $html = '';
        $html .= '<i class="backtrace_link" onclick="backtraceToggle(' . ++$index . ')">';
        $html .= '<u>debug_backtrace output</u></i>';
        $html .= '<div id="debug_backtrace' . $index . '" class="backtrace_table" style="display:none;">';
        $html .= '<table>';
        foreach ($trace as $item) {
            if (isset( $item['function'] ) && $item['function'] == __FUNCTION__) {
                continue;
            }

            $html .= '<tr><td>';
            if (isset( $item['class'] )) {
                $html .= '<b>' . $item['class'] . '</b>' . $item['type'];
            }
            if (isset( $item['function'] )) {
                if (isset( $item['class'] )) {
                    $html .= '<span style="color:#f0f">';
                }
                $html .= $item['function'];
                if (isset( $item['class'] )) {
                    $html .= '</span>';
                }
                $html .= '();';
            }
            $html .= '</td><td>';
            $html .= ( isset( $item['file'] ) ) ? str_replace(DIRECTORY_SEPARATOR, '/', str_replace(SHARED_PATH, '', $item['file'])) : '';
            $html .= ( isset( $item['line'] ) ) ? ' (<b>' . $item['line'] . '</b>)' : '';
            $html .= '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public static function dump($source, $title = '', $exit = false)
    {
        $exit = (bool)$exit;

        if (is_string($title)) {
            $title = htmlspecialchars($title);
        }

        if (is_int($title)) {
            $exit  = (bool)$title;
            $title = '';
        }

        $trace = debug_backtrace();

        self::_renderStyles();
        self::_render('dump', array(
            'title'  => $title,
            'source' => $source,
            'trace'  => $trace,
        ));

        if ($exit) {
            die();
        }
    }

    public static function dumpTable(array $data)
    {
        $trace = debug_backtrace();
        self::_renderStyles();
        echo '<div class="df-dump df-dump-table-wrapper">';
        echo '<table class="df-dump-table" border="1" style="font-size: 12px;width: 100%;border-collapse: collapse;border-spacing: 0;">';
        $first = true;
        foreach ($data as $row) {
            if ($first) {
                echo '<tr>';
                foreach ($row as $title => $col) {
                    echo '<th>' . htmlspecialchars($title) . '</th>';
                }
                echo '</tr>';
                $first = false;
            }
            echo '<tr>';
            foreach ($row as $col) {
                if (is_array($col) or is_object($col)) {
                    echo '<td>' . json_encode(htmlspecialchars($col)) . '</td>';
                } else {
                    echo '<td>' . htmlspecialchars($col) . '</td>';
                }
            }
            echo '</tr>';
        }
        echo '</table>';
        echo '<div class="df-stack-trace">'. (str_replace(Dumper::$ROOT, '', $trace[0]['file']) . ':' . $trace[0]['line']).'</div>';
        echo '</div>';
    }

    private static function _render($template, $data = array())
    {
        extract($data);
        $templateFilename = __DIR__ . DIRECTORY_SEPARATOR . $template . '.tmpl.php';
        include $templateFilename;
    }

    private static function _dump($source)
    {
        $maxTextLength = 60;
        if (is_array($source)) {
            self::_dumpArray($source);
        } elseif (is_object($source)) {
            self::_dumpObject($source);
        } elseif (is_string($source)) {
            if (mb_strlen($source, 'UTF-8') > $maxTextLength) {
                $short = mb_strcut($source, 0, $maxTextLength, 'UTF-8');
                echo "(string) <span style='color:blue' title='$source'>'" . htmlspecialchars($short) . "...'</span>\n";
            } else {
                echo "(string) <span style='color:blue'>'" . htmlspecialchars($source) . "'</span>\n";
            }
        } elseif (is_int($source)) {
            echo "(int)    <span style='color:red'>$source</span>\n";
        } elseif (is_float($source)) {
            echo "(float)  <span style='color:#ff00ff'>$source</span>\n";
        } elseif (is_null($source)) {
            echo "(null)   <b>NULL</b>\n";
        } elseif (is_bool($source)) {
            echo $source ? "(bool)   <b>TRUE</b>\n" : "(bool)   <b>FALSE</b>\n";
        } else {
            echo "(?)      <i>" . htmlspecialchars($source) . "</i>\n";
        }
    }

    private static function _dumpObject($source)
    {
        $reflection = new \ReflectionObject($source);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $count      = count($properties);
        $onClick    = $count ? "onclick=\"dfDumpToggle('df-" . self::$id . "')\"" : '';
        echo "<span class='df-switch' $onClick>" .
            "Object[$count] <span style='color:#000'>" . get_class($source) . "</span></span> {";
        if ($count) {
            echo "<div class='df-cont" . ( !self::EXPAND ? "-closed" : '' ) . "' id='df-" . ( self::$id++ ) . "'>";
            $maxKeyLength = self::_dumpGetMaxKeyLength($properties);
            foreach ($properties as $property) {
                $name  = $property->name;
                $value = $source->$name;

                $switch = is_array($value) || is_object($value);

                $key = str_pad($name, $maxKeyLength, ' ');
                echo $switch ? "+" : ' ';
                echo "<i class='df-key" . ( $switch ? "-closed" : '' ) . "'";
                echo $switch ? " onclick=\"dfDumpToggle('df-" . self::$id . "')\"" : '';
                echo ">$key</i> => ";
                self::_dump($value);
            }
            echo "</div>";
        } else {
            self::$id++;
        }
        echo "}\n";
    }

    private static function _dumpArray($array)
    {
        $count   = count($array);
        $onClick = $count ? "onclick=\"dfDumpToggle('df-" . self::$id . "')\"" : '';
        echo "<span class='df-switch' $onClick>Array[$count]</span> (";
        if ($count) {
            echo "<div class='df-cont" . ( !self::EXPAND ? "-closed" : '' ) . "' id='df-" . ( self::$id++ ) . "'>";
            $maxKeyLength = self::_dumpGetMaxKeyLength(array_keys($array));
            foreach ($array as $key => $value) {
                $switch = is_array($value) || is_object($value);

                $key = str_pad($key, $maxKeyLength, ' ');
                echo is_array($value) ? "+" : ' ';
                echo "<i class='df-key" . ( $switch ? "-closed" : '' ) . "'";
                echo $switch ? " onclick=\"dfDumpToggle('df-" . self::$id . "')\"" : '';
                echo ">$key</i> => ";
                self::_dump($value);
            }
            echo "</div>";
            echo ")\n";
        } else {
            self::$id++;
            echo "<b>EMPTY</b>)\n";
        }
    }

    private static function _dumpGetMaxKeyLength($keys)
    {
        $max = 0;
        foreach ($keys as $key) {
            if (is_object($key)) {
                $len = strlen((string)$key->name);
            } else {
                $len = strlen((string)$key);
            }
            $max = ( $len > $max ) ? $len : $max;
        }

        return $max;
    }

    private static function _renderStyles()
    {
        static $css;

        if (!$css) {
            $css = true;
            echo '<body>'; // for browser-sync live reload
            self::_render('styles');
        }
    }
}
