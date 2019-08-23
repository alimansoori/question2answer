<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Server-side response to Ajax request based on ask a question title


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'util/string.php';
require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';


// Collect the information we need from the database

$intitle = ilya_post_text('title');
$doaskcheck = ilya_opt('do_ask_check_qs');
$doexampletags = ilya_using_tags() && ilya_opt('do_example_tags');

if ($doaskcheck || $doexampletags) {
	$countqs = max($doexampletags ? ILYA__DB_RETRIEVE_ASK_TAG_QS : 0, $doaskcheck ? ilya_opt('page_size_ask_check_qs') : 0);

	$relatedquestions = ilya_db_select_with_pending(
		ilya_db_search_posts_selectspec(null, ilya_string_to_words($intitle), null, null, null, null, 0, false, $countqs)
	);
}


// Collect example tags if appropriate

if ($doexampletags) {
	$tagweight = array();
	foreach ($relatedquestions as $question) {
		$tags = ilya_tagstring_to_tags($question['tags']);
		foreach ($tags as $tag) {
			@$tagweight[$tag] += exp($question['score']);
		}
	}

	arsort($tagweight, SORT_NUMERIC);

	$exampletags = array();

	$minweight = exp(ilya_match_to_min_score(ilya_opt('match_example_tags')));
	$maxcount = ilya_opt('page_size_ask_tags');

	foreach ($tagweight as $tag => $weight) {
		if ($weight < $minweight)
			break;

		$exampletags[] = $tag;
		if (count($exampletags) >= $maxcount)
			break;
	}
} else {
	$exampletags = array();
}


// Output the response header and example tags

echo "ILYA__AJAX_RESPONSE\n1\n";

echo strtr(ilya_html(implode(',', $exampletags)), "\r\n", '  ') . "\n";


// Collect and output the list of related questions

if ($doaskcheck) {
	$minscore = ilya_match_to_min_score(ilya_opt('match_ask_check_qs'));
	$maxcount = ilya_opt('page_size_ask_check_qs');

	$relatedquestions = array_slice($relatedquestions, 0, $maxcount);
	$limitedquestions = array();

	foreach ($relatedquestions as $question) {
		if ($question['score'] < $minscore)
			break;

		$limitedquestions[] = $question;
	}

	$themeclass = ilya_load_theme_class(ilya_get_site_theme(), 'ajax-asktitle', null, null);
	$themeclass->initialize();
	$themeclass->q_ask_similar($limitedquestions, ilya_lang_html('question/ask_same_q'));
}
