<?php
/**
 * @deprecated This file is deprecated from ILYA 1.7; use the below file instead.
 */

if (!defined('ILYA_VERSION')) {
	header('Location: ../');
	exit;
}

if (defined('ILYA_DEBUG_PERFORMANCE') && ILYA_DEBUG_PERFORMANCE) {
	trigger_error('Included file ' . basename(__FILE__) . ' is deprecated');
}

return (include ILYA_INCLUDE_DIR.'lang/ilya-lang-users.php');
