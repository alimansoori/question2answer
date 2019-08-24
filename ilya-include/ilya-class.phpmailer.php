<?php
/**
 * @deprecated This file is deprecated; please use ILYA built-in functions for sending emails.
 */

if (!defined('ILYA_VERSION')) {
	header('Location: ../');
	exit;
}

if (defined('ILYA_DEBUG_PERFORMANCE') && ILYA_DEBUG_PERFORMANCE) {
	trigger_error('Included file ' . basename(__FILE__) . ' is deprecated');
}

require_once 'vendor/PHPMailer/PHPMailerAutoload.php';
