<?php
if (!defined('ILYA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
require_once ILYA_INCLUDE_DIR.'ilya-filter-basic.php';
require_once ILYA_INCLUDE_DIR.'ilya-app-upload.php';
require_once ILYA_PLUGIN_DIR.'gallery-image-post/ilya-gallery.php';

$ilya_gallery_images;

class ilya_gallery_filter {
	public function filter_question(&$question, &$errors, $oldquestion) {
		global $ilya_gallery_images;
		$ilya_gallery_images = [];
		$fb = new ilya_filter_basic();
		for($key=1; $key<=ilya_gallery::FIELD_COUNT_MAX; $key++) {
			if(ilya_opt(ilya_gallery::FIELD_ACTIVE.$key)) {
				$name = ilya_gallery::FIELD_BASE_NAME.$key;
				$extradata = '';
				$checkvalue = '';
				if(ilya_opt(ilya_gallery::FIELD_TYPE.$key) != ilya_gallery::FIELD_TYPE_FILE) {
					$extradata = ilya_post_text($name);
					$checkvalue = $extradata;
				} else {
					$extradata = $this->file_info($name);
					if(!empty($extradata))
						$checkvalue = $extradata['name'];
					else {
						$oldextradata = ilya_post_text($name.'-old');
						if(!empty($oldextradata))
							$checkvalue = $oldextradata;
					}
				}
				if(ilya_opt(ilya_gallery::FIELD_REQUIRED.$key)) {
					$fb->validate_length($errors, $name, $checkvalue, 1, ILYA_DB_MAX_CONTENT_LENGTH);
					if(array_key_exists($name, $errors))
						$ilya_gallery_images[$name]['error'] = ilya_lang_sub(ilya_gallery::PLUGIN.'/'.ilya_gallery::FIELD_REQUIRED.'_message',ilya_opt(ilya_gallery::FIELD_PROMPT.$key));
				}
				if(ilya_opt(ilya_gallery::FIELD_TYPE.$key) == ilya_gallery::FIELD_TYPE_FILE) {
					if(!empty($extradata)) {
						$file_info = $this->file_info($name);
						if(is_array($file_info)) {
							$extstr = ilya_opt(ilya_gallery::FIELD_OPTION.$key);
							if(!empty($extstr)) {
								$exts = explode(',', $extstr);
								$names = explode('.', $file_info['name']);
								if(count($names)>=2) {
									$ext = $names[count($names)-1];
									if(!in_array($ext, $exts))
										$ilya_gallery_images[$name]['error'] = ilya_lang_sub(ilya_gallery::PLUGIN.'/'.ilya_gallery::FIELD_OPTION_EXT_ERROR, $extstr);
								} else
									$ilya_gallery_images[$name]['error'] = ilya_lang_sub(ilya_gallery::PLUGIN.'/'.ilya_gallery::FIELD_OPTION_EXT_ERROR, $extstr);
							}
							if(!isset($ilya_gallery_images[$name]['error'])) {
								$result = ilya_upload_file(
									$file_info['tmp_name'],
									$file_info['name'],
									ilya_opt(ilya_gallery::MAXFILE_SIZE),
									ilya_opt(ilya_gallery::ONLY_IMAGE),
									ilya_opt(ilya_gallery::IMAGE_MAXWIDTH),
									ilya_opt(ilya_gallery::IMAGE_MAXHEIGHT)
									);
								if(isset($result['error']))
									$ilya_gallery_images[$name]['error'] = $result['error'];
								else
									$extradata = $result['blobid'];
							}
						}
					} else {
						$oldextradata = ilya_post_text($name.'-old');
						if(!empty($oldextradata)) {
							if(ilya_post_text($name.'-remove'))
								$extradata = '';
							else
								$extradata = $oldextradata;
						}
					}
				}
				if(isset($ilya_gallery_images[$name]['error']))
					$errors[$name] = $ilya_gallery_images[$name]['error'];
				else
					$ilya_gallery_images[$name]['value'] = $extradata;
			}
		}
	}

	public function file_info($name) {
		if(array_key_exists($name, $_FILES) && $_FILES[$name]['name'] != '')
			return $_FILES[$name];
		else
			return '';
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/