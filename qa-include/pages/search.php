<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for search page


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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'app/format.php';
require_once QA_INCLUDE_DIR . 'app/options.php';
require_once QA_INCLUDE_DIR . 'app/search.php';


// Perform the search if appropriate

if (strlen(ilya_get('q'))) {
	// Pull in input parameters
	$inquery = trim(ilya_get('q'));
	$userid = ilya_get_logged_in_userid();
	$start = ilya_get_start();

	$display = ilya_opt_if_loaded('page_size_search');
	$count = 2 * (isset($display) ? $display : QA_DB_RETRIEVE_QS_AS) + 1;
	// get enough results to be able to give some idea of how many pages of search results there are

	// Perform the search using appropriate module

	$results = ilya_get_search_results($inquery, $start, $count, $userid, false, false);

	// Count and truncate results

	$pagesize = ilya_opt('page_size_search');
	$gotcount = count($results);
	$results = array_slice($results, 0, $pagesize);

	// Retrieve extra information on users

	$fullquestions = array();

	foreach ($results as $result) {
		if (isset($result['question']))
			$fullquestions[] = $result['question'];
	}

	$usershtml = ilya_userids_handles_html($fullquestions);

	// Report the search event

	ilya_report_event('search', $userid, ilya_get_logged_in_handle(), ilya_cookie_get(), array(
		'query' => $inquery,
		'start' => $start,
	));
}


// Prepare content for theme

$ilya_content = ilya_content_prepare(true);

if (strlen(ilya_get('q'))) {
	$ilya_content['search']['value'] = ilya_html($inquery);

	if (count($results))
		$ilya_content['title'] = ilya_lang_html_sub('main/results_for_x', ilya_html($inquery));
	else
		$ilya_content['title'] = ilya_lang_html_sub('main/no_results_for_x', ilya_html($inquery));

	$ilya_content['q_list']['form'] = array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'hidden' => array(
			'code' => ilya_get_form_security_code('vote'),
		),
	);

	$ilya_content['q_list']['qs'] = array();

	$qdefaults = ilya_post_html_defaults('Q');

	foreach ($results as $result) {
		if (!isset($result['question'])) { // if we have any non-question results, display with less statistics
			$qdefaults['voteview'] = false;
			$qdefaults['answersview'] = false;
			$qdefaults['viewsview'] = false;
			break;
		}
	}

	foreach ($results as $result) {
		if (isset($result['question'])) {
			$fields = ilya_post_html_fields($result['question'], $userid, ilya_cookie_get(),
				$usershtml, null, ilya_post_html_options($result['question'], $qdefaults));
		} elseif (isset($result['url'])) {
			$fields = array(
				'what' => ilya_html($result['url']),
				'meta_order' => ilya_lang_html('main/meta_order'),
			);
		} else {
			continue; // nothing to show here
		}

		if (isset($qdefaults['blockwordspreg']))
			$result['title'] = ilya_block_words_replace($result['title'], $qdefaults['blockwordspreg']);

		$fields['title'] = ilya_html($result['title']);
		$fields['url'] = ilya_html($result['url']);

		$ilya_content['q_list']['qs'][] = $fields;
	}

	$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pagesize, $start + $gotcount,
		ilya_opt('pages_prev_next'), array('q' => $inquery), $gotcount >= $count);

	if (ilya_opt('feed_for_search')) {
		$ilya_content['feed'] = array(
			'url' => ilya_path_html(ilya_feed_request('search/' . $inquery)),
			'label' => ilya_lang_html_sub('main/results_for_x', ilya_html($inquery)),
		);
	}

	if (empty($ilya_content['page_links']))
		$ilya_content['suggest_next'] = ilya_html_suggest_qs_tags(ilya_using_tags());

} else
	$ilya_content['error'] = ilya_lang_html('main/search_explanation');


return $ilya_content;
