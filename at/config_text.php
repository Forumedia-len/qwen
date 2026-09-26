<?php

$alias = Service::request()->_('alias', 'address');
$alias = preg_match('/^[a-z0-9_]+$/i', $alias) ? strtolower($alias) : 'address';

Service::redirect()->redirect(site_url('text/alias/' . $alias))->send();
exit;