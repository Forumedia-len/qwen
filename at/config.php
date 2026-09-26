<?php

$_page = \Service::app()->execModule('config', ['mode' => Service::request()->_('mode', 'count')]);

