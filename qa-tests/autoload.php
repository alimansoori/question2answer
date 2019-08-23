<?php
// currently, all Q2A code depends on ilya-base

global $qa_options_cache;

// Needed in order to avoid accessing the database while including the ilya-base.php file
$qa_options_cache['enabled_plugins'] = '';

$qa_autoconnect = false;
require_once __DIR__.'/../ilya-include/ilya-base.php';
