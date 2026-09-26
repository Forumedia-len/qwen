<?php

use AC\core\modules\text\engines\TextEngine;

$_page['key'] = 'sepa_help';

ob_start ();

/** @var TextEngine $txt */
$txt = getEngine('text', false);
if($txt?->getContent($row, 'sepa') && !empty($row['content']))
{
   echo $row['content'];
}

$_page['content'][0] = ob_get_contents ();
ob_end_clean ();;
