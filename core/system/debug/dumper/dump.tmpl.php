<?php


/**
 * @var string $title
 * @var mixed $source
 * @var array $trace
 */

use AC\core\system\debug\dumper\Dumper;

?>

<div class="df-dump">
    <?php if ($title) { ?>
        <div class="df-dump-title"><?= htmlspecialchars($title) ?></div>
    <?php } ?>
    <pre class="df-dump-pre"><?php self::_dump($source) ?></pre>
    <div class="df-stack-trace"><?= (str_replace(Dumper::$ROOT, '', $trace[0]['file']) . ':' . $trace[0]['line']) ?></div>
</div>

