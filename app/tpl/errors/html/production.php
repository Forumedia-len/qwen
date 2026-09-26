<?php

use AC\core\system\view\HttpErrorPage;

// Текст исключения и диагностические данные не попадают в пользовательский шаблон.
echo (new HttpErrorPage())->render(isset($code) && $code >= 400 && $code <= 599 ? (int)$code : 500);
