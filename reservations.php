<?php

$_page = Service::app()->execModule('reservations');

$_page['js'][]   = ['select2.min', 'third/select2', false, 'cdn'];
$_page['css'][]  = ['select2.min', 'third/select2', false, 'cdn'];
$_page['js'][]   = ['popup_box', 'common', false, 'cdn'];
$_page['css'][]  = ['popup_box', 'common', false, 'cdn'];
$_page['head'][] = '<meta name="robots" content="noindex, nofollow">';
$_page['class']  = 'reservation-block';
