<?php

use AC\core\system\view\HttpErrorPage;

// Публичное сообщение передаётся отдельно от диагностического $message исключения.
echo (new HttpErrorPage())->render(404, $publicMessage ?? null);
