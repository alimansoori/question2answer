<?php
/**
 * @deprecated This file is deprecated from ILYA 1.7; use the below file instead.
 */

if (!defined('ILYA__VERSION')) {
	header('Location: ../');
	exit;
}

if (defined('ILYA__DEBUG_PERFORMANCE') && ILYA__DEBUG_PERFORMANCE) {
	trigger_error('Included file ' . basename(__FILE__) . ' is deprecated');
}

return (include ILYA__INCLUDE_DIR.'lang/ilya-lang-profile.php');
