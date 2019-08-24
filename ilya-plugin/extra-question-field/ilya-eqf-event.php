<?php
if (!defined('ILYA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
require_once ILYA_INCLUDE_DIR.'ilya-db-metas.php';
require_once ILYA_PLUGIN_DIR.'extra-question-field/ilya-eqf.php';

class ilya_eqf_event {

	function process_event ($event, $userid, $handle, $cookieid, $params) {
		global $ilya_extra_question_fields;
		switch ($event) {
		case 'q_queue':
		case 'q_post':
		case 'q_edit':
			for($key=1; $key<=ilya_eqf::FIELD_COUNT_MAX; $key++) {
				if((bool)ilya_opt(ilya_eqf::FIELD_ACTIVE.$key)) {
					$name = ilya_eqf::FIELD_BASE_NAME.$key;
					if(isset($ilya_extra_question_fields[$name]))
						$content = ilya_sanitize_html($ilya_extra_question_fields[$name]['value']);
					else
						$content = ilya_db_single_select(ilya_db_post_meta_selectspec($params['postid'], 'ilya_q_'.$name));
					if(is_null($content))
						$content = '';
					ilya_db_postmeta_set($params['postid'], 'ilya_q_'.$name, $content);
				}
			}
			break;
		case 'q_delete':
			for($key=1; $key<=ilya_eqf::FIELD_COUNT_MAX; $key++) {
				if((bool)ilya_opt(ilya_eqf::FIELD_ACTIVE.$key)) {
					$name = ilya_eqf::FIELD_BASE_NAME.$key;
					ilya_db_postmeta_clear($params['postid'], 'ilya_q_'.$name);
				}
			}
			break;
		}
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/