<?php
/**
 * @deprecated This file is deprecated from Q2A 1.7; use Q2A_Util_Usage class (Q2A/Util/Usage.php) instead.
 *
 * The functions in this file are maintained for backwards compatibility, but simply call through to the
 * new class where applicable.
 */

if (!defined('QA_VERSION')) {
	header('Location: ../');
	exit;
}

if (defined('QA_DEBUG_PERFORMANCE') && QA_DEBUG_PERFORMANCE) {
	trigger_error('Included file ' . basename(__FILE__) . ' is deprecated');
}

function ilya_usage_init()
{
	// should already be initialised in ilya-base.php
	global $ilya_usage;
	if (empty($ilya_usage))
		$ilya_usage = new Q2A_Util_Usage;
}

function ilya_usage_get()
{
	global $ilya_usage;
	return $ilya_usage->getCurrent();
}

function ilya_usage_delta($oldusage, $newusage)
{
	// equivalent function is now private
	return array();
}

function ilya_usage_mark($stage)
{
	global $ilya_usage;
	return $ilya_usage->mark($stage);
}

function ilya_usage_line($stage, $usage, $totalusage)
{
	// equivalent function is now private
	return '';
}

function ilya_usage_output()
{
	global $ilya_usage;
	return $ilya_usage->output();
}
