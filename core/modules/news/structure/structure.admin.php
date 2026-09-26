<?php

$admin = [];

//новости
$admin['news']['parent_key'] = 'login';
$admin['news']['template']   = 'internal';
$admin['news']['icon']       = 'news';
$admin['news']['title']      = lang('news_title', 'structure');
$admin['news']['href']       = 'news';
$admin['news']['sort']       = 340;

return $admin;