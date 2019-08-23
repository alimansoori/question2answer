<?php
/**
 * @deprecated This file is deprecated; please use ILYA built-in functions for sending emails.
 */

if (!defined('ILYA__VERSION')) {
	header('Location: ../');
	exit;
}

if (defined('ILYA__DEBUG_PERFORMANCE') && ILYA__DEBUG_PERFORMANCE) {
	trigger_error('Included file ' . basename(__FILE__) . ' is deprecated');
}

require_once 'vendor/PHPMailer/PHPMailerAutoload.php';
