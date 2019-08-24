<?php
if (!defined('ILYA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
class ilya_gallery {
	const PLUGIN						= 'gallery_image';
	const FIELD_BASE_NAME				= 'gallery';
	const FIELD_COUNT					= 'gallery_count';
	const FIELD_COUNT_DFL				= 3;
	const FIELD_COUNT_MAX				= 20;
	const MAXFILE_SIZE					= 'gallery_image_maxfile_size';
	const MAXFILE_SIZE_DFL				= 2097152;
	const ONLY_IMAGE					= 'gallery_only_image';
	const ONLY_IMAGE_DFL				= false;	// Can't change true.
	const IMAGE_MAXWIDTH				= 'gallery_image_maxwidth';
	const IMAGE_MAXWIDTH_DFL			= 600;
	const IMAGE_MAXHEIGHT				= 'gallery_image_maxheight';
	const IMAGE_MAXHEIGHT_DFL			= 600;
	const THUMB_SIZE					= 'gallery_thumb_size';
	const THUMB_SIZE_DFL				= 100;
	const LIGHTBOX_EFFECT				= 'gallery_lightbox_effect';
	const LIGHTBOX_EFFECT_DFL			= false;	// Can't change true.
	const FIELD_ACTIVE					= 'gallery_active';
	const FIELD_ACTIVE_DFL				= false;	// Can't change true.
	const FIELD_PROMPT					= 'gallery_prompt';
	const FIELD_NOTE					= 'gallery_note';
	const FIELD_NOTE_HEIGHT				= 2;
	const FIELD_TYPE					= 'gallery_type';
	const FIELD_TYPE_DFL				= 'text';
	const FIELD_TYPE_TEXT				= 'text';
	const FIELD_TYPE_TEXT_LABEL			= 'gallery_type_text';
	const FIELD_TYPE_TEXTAREA			= 'textarea';
	const FIELD_TYPE_TEXTAREA_LABEL		= 'gallery_type_textarea';
	const FIELD_TYPE_CHECK				= 'checkbox';
	const FIELD_TYPE_CHECK_LABEL		= 'gallery_type_checkbox';
	const FIELD_TYPE_SELECT				= 'select';
	const FIELD_TYPE_SELECT_LABEL		= 'gallery_type_select';
	const FIELD_TYPE_RADIO				= 'select-radio';
	const FIELD_TYPE_RADIO_LABEL		= 'gallery_type_radio';
	const FIELD_TYPE_FILE				= 'file';
	const FIELD_TYPE_FILE_LABEL			= 'gallery_type_file';
	const FIELD_OPTION					= 'gallery_option';
	const FIELD_OPTION_HEIGHT			= 2;
	const FIELD_OPTION_ROWS_DFL			= 3;
	const FIELD_OPTION_EXT_ERROR		= 'gallery_option_ext_error';
	const FIELD_ATTR					= 'gallery_attr';
	const FIELD_DEFAULT					= 'gallery_default';
	const FIELD_FORM_POS				= 'gallery_form_pos';
	const FIELD_FORM_POS_DFL			= 'content';
	const FIELD_FORM_POS_TOP			= 'top';
	const FIELD_FORM_POS_TOP_LABEL		= 'gallery_form_pos_top';
	const FIELD_FORM_POS_CUSTOM			= 'custom';
	const FIELD_FORM_POS_CUSTOM_LABEL	= 'gallery_form_pos_custom';
	const FIELD_FORM_POS_TITLE			= 'title';
	const FIELD_FORM_POS_TITLE_LABEL	= 'gallery_form_pos_title';
	const FIELD_FORM_POS_CATEGORY		= 'category';
	const FIELD_FORM_POS_CATEGORY_LABEL	= 'gallery_form_pos_category';
	const FIELD_FORM_POS_CONTENT		= 'content';
	const FIELD_FORM_POS_CONTENT_LABEL	= 'gallery_form_pos_content';
	const FIELD_FORM_POS_EXTRA			= 'extra';
	const FIELD_FORM_POS_EXTRA_LABEL	= 'gallery_form_pos_extra';
	const FIELD_FORM_POS_TAGS			= 'tags';
	const FIELD_FORM_POS_TAGS_LABEL		= 'gallery_form_pos_tags';
	const FIELD_FORM_POS_NOTIFY			= 'notify';
	const FIELD_FORM_POS_NOTIFY_LABEL	= 'gallery_form_pos_notify';
	const FIELD_FORM_POS_BOTTOM			= 'bottom';
	const FIELD_FORM_POS_BOTTOM_LABEL	= 'gallery_form_pos_bottom';
	const FIELD_DISPLAY					= 'gallery_display';
	const FIELD_DISPLAY_DFL				= false;	// Can't change true.
	const FIELD_LABEL					= 'gallery_label';
	const FIELD_PAGE_POS				= 'gallery_page_pos';
	const FIELD_PAGE_POS_DFL			= 'below';
	const FIELD_PAGE_POS_UPPER			= 'upper';
	const FIELD_PAGE_POS_UPPER_LABEL	= 'gallery_page_pos_upper';
	const FIELD_PAGE_POS_INSIDE			= 'inside';
	const FIELD_PAGE_POS_INSIDE_LABEL	= 'gallery_page_pos_inside';
	const FIELD_PAGE_POS_BELOW			= 'below';
	const FIELD_PAGE_POS_BELOW_LABEL	= 'gallery_page_pos_below';
	const FIELD_PAGE_POS_HOOK			= '[*attachment^*]';
	const FIELD_HIDE_BLANK				= 'gallery_hide_blank';
	const FIELD_HIDE_BLANK_DFL			= false;	// Can't change true.
	const FIELD_REQUIRED				= 'gallery_required';
	const FIELD_REQUIRED_DFL			= false;	// Can't change true.
	const SAVE_BUTTON					= 'gallery_save_button';
	const DFL_BUTTON					= 'gallery_dfl_button';
	const SAVED_MESSAGE					= 'gallery_saved_message';
	const RESET_MESSAGE					= 'gallery_reset_message';
	
	var $directory;
	var $urltoroot;

	var $gallery_count;
	var $gallery_maxfile_size;
	var $gallery_only_image;
	var $gallery_image_maxwidth;
	var $gallery_image_maxheight;
	var $gallery_thumb_size;
	var $gallery_lightbox_effect;
	var $gallery;
	var $gallery_note_height;
	var $gallery_option_height;

	function __construct() {
		$this->gallery_count = self::FIELD_COUNT_DFL;
		$this->gallery_maxfile_size = self::MAXFILE_SIZE_DFL;
		$this->gallery_only_image = self::ONLY_IMAGE_DFL;
		$this->gallery_image_maxwidth = self::IMAGE_MAXWIDTH_DFL;
		$this->gallery_image_maxheight = self::IMAGE_MAXHEIGHT_DFL;
		$this->gallery_thumb_size = self::THUMB_SIZE_DFL;
		$this->gallery_lightbox_effect = self::LIGHTBOX_EFFECT_DFL;
		$this->init_gallery($this->gallery_count);
		$this->gallery_note_height = self::FIELD_NOTE_HEIGHT;
		$this->gallery_option_height = self::FIELD_OPTION_HEIGHT;
	}
	function init_gallery($count) {
		$this->gallery = array();
		for($key=1; $key<=$count; $key++) {
			$this->gallery[(string)$key] = array(
				'active' => self::FIELD_ACTIVE_DFL,
				'prompt' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_PROMPT.'_default',$key),
				'note' => '',
				'type' => self::FIELD_TYPE_DFL,
				'attr' => '',
				'option' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_OPTION.'_default',$key),
				'default' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_DEFAULT.'_default',$key),
				'form_pos' => self::FIELD_FORM_POS_DFL,
				'display' => self::FIELD_DISPLAY_DFL,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_LABEL.'_default',$key),
				'page_pos' => self::FIELD_PAGE_POS_DFL,
				'displayblank' => self::FIELD_HIDE_BLANK_DFL,
				'required' => self::FIELD_REQUIRED_DFL,
			);
		}
	}
	function load_module($directory, $urltoroot) {
		$this->directory=$directory;
		$this->urltoroot=$urltoroot;
	}
	function option_default($option) {
		if ($option==self::FIELD_COUNT) return $this->gallery_count;
		if ($option==self::MAXFILE_SIZE) return $this->gallery_maxfile_size;
		if ($option==self::ONLY_IMAGE) return $this->gallery_only_image;
		if ($option==self::IMAGE_MAXWIDTH) return $this->gallery_image_maxwidth;
		if ($option==self::IMAGE_MAXHEIGHT) return $this->gallery_image_maxheight;
		if ($option==self::THUMB_SIZE) return $this->gallery_thumb_size;
		if ($option==self::LIGHTBOX_EFFECT) return $this->gallery_lightbox_effect;
		foreach ($this->gallery as $key => $gallery) {
			if ($option==self::FIELD_ACTIVE.$key) return $gallery['active'];
			if ($option==self::FIELD_PROMPT.$key) return $gallery['prompt'];
			if ($option==self::FIELD_NOTE.$key) return $gallery['note'];
			if ($option==self::FIELD_TYPE.$key) return $gallery['type'];
			if ($option==self::FIELD_OPTION.$key) return $gallery['option'];
			if ($option==self::FIELD_ATTR.$key) return $gallery['attr'];
			if ($option==self::FIELD_DEFAULT.$key) return $gallery['default'];
			if ($option==self::FIELD_FORM_POS.$key) return $gallery['form_pos'];
			if ($option==self::FIELD_DISPLAY.$key) return $gallery['display'];
			if ($option==self::FIELD_LABEL.$key) return $gallery['label'];
			if ($option==self::FIELD_PAGE_POS.$key) return $gallery['page_pos'];
			if ($option==self::FIELD_HIDE_BLANK.$key) return $gallery['displayblank'];
			if ($option==self::FIELD_REQUIRED.$key) return $gallery['required'];
		}
	}
	function admin_form(&$ilya_content) {
		$saved = '';
		$error = false;
		$error_active = array();
		$error_prompt = array();
		$error_note = array();
		$error_type = array();
		$error_attr = array();
		$error_option = array();
		$error_default = array();
		$error_form_pos = array();
		$error_display = array();
		$error_label = array();
		$error_page_pos = array();
		$error_hide_blank = array();
		$error_required = array();
		for($key=1; $key<=self::FIELD_COUNT_MAX; $key++) {
			$error_active[$key] = '';
			$error_prompt[$key] = '';
			$error_note[$key] = '';
			$error_type[$key] = '';
			$error_attr[$key] = '';
			$error_option[$key] = '';
			$error_default[$key] = '';
			$error_form_pos[$key] = '';
			$error_display[$key] = '';
			$error_label[$key] = '';
			$error_page_pos[$key] = '';
			$error_hide_blank[$key] = '';
			$error_required[$key] = '';
		}
		if (ilya_clicked(self::SAVE_BUTTON)) {
			ilya_opt(self::FIELD_COUNT, ilya_post_text(self::FIELD_COUNT.'_field'));
			ilya_opt(self::MAXFILE_SIZE, ilya_post_text(self::MAXFILE_SIZE.'_field'));
			ilya_opt(self::ONLY_IMAGE, (int)ilya_post_text(self::ONLY_IMAGE.'_field'));
			ilya_opt(self::IMAGE_MAXWIDTH, ilya_post_text(self::IMAGE_MAXWIDTH.'_field'));
			ilya_opt(self::IMAGE_MAXHEIGHT, ilya_post_text(self::IMAGE_MAXHEIGHT.'_field'));
			ilya_opt(self::THUMB_SIZE, ilya_post_text(self::THUMB_SIZE.'_field'));
			ilya_opt(self::LIGHTBOX_EFFECT, (int)ilya_post_text(self::LIGHTBOX_EFFECT.'_field'));
			$this->init_gallery(ilya_post_text(self::FIELD_COUNT.'_field'));
			foreach ($this->gallery as $key => $gallery) {
				if (trim(ilya_post_text(self::FIELD_PROMPT.'_field'.$key)) == '') {
					$error_prompt[$key] = ilya_lang(self::PLUGIN.'/'.self::FIELD_PROMPT.'_error');
					$error = true;
				}
				if (ilya_post_text(self::FIELD_TYPE.'_field'.$key) != self::FIELD_TYPE_TEXT
				&& ilya_post_text(self::FIELD_TYPE.'_field'.$key) != self::FIELD_TYPE_TEXTAREA
				&& ilya_post_text(self::FIELD_TYPE.'_field'.$key) != self::FIELD_TYPE_FILE
				&& trim(ilya_post_text(self::FIELD_OPTION.'_field'.$key)) == '') {
					$error_option[$key] = ilya_lang(self::PLUGIN.'/'.self::FIELD_OPTION.'_error');
					$error = true;
				}
				/*
				if ((bool)ilya_post_text(self::FIELD_DISPLAY.'_field'.$key) && trim(ilya_post_text(self::FIELD_LABEL.'_field'.$key)) == '') {
					$error_label[$key] = ilya_lang(self::PLUGIN.'/'.self::FIELD_LABEL.'_error');
					$error = true;
				}
				*/
			}
			foreach ($this->gallery as $key => $gallery) {
				ilya_opt(self::FIELD_ACTIVE.$key, (int)ilya_post_text(self::FIELD_ACTIVE.'_field'.$key));
				ilya_opt(self::FIELD_PROMPT.$key, ilya_post_text(self::FIELD_PROMPT.'_field'.$key));
				ilya_opt(self::FIELD_NOTE.$key, ilya_post_text(self::FIELD_NOTE.'_field'.$key));
				ilya_opt(self::FIELD_TYPE.$key, ilya_post_text(self::FIELD_TYPE.'_field'.$key));
				ilya_opt(self::FIELD_OPTION.$key, ilya_post_text(self::FIELD_OPTION.'_field'.$key));
				ilya_opt(self::FIELD_ATTR.$key, ilya_post_text(self::FIELD_ATTR.'_field'.$key));
				ilya_opt(self::FIELD_DEFAULT.$key, ilya_post_text(self::FIELD_DEFAULT.'_field'.$key));
				ilya_opt(self::FIELD_FORM_POS.$key, ilya_post_text(self::FIELD_FORM_POS.'_field'.$key));
				ilya_opt(self::FIELD_DISPLAY.$key, (int)ilya_post_text(self::FIELD_DISPLAY.'_field'.$key));
				ilya_opt(self::FIELD_LABEL.$key, ilya_post_text(self::FIELD_LABEL.'_field'.$key));
				ilya_opt(self::FIELD_PAGE_POS.$key, ilya_post_text(self::FIELD_PAGE_POS.'_field'.$key));
				ilya_opt(self::FIELD_HIDE_BLANK.$key, (int)ilya_post_text(self::FIELD_HIDE_BLANK.'_field'.$key));
				ilya_opt(self::FIELD_REQUIRED.$key, (int)ilya_post_text(self::FIELD_REQUIRED.'_field'.$key));
			}
			$saved = ilya_lang_html(self::PLUGIN.'/'.self::SAVED_MESSAGE);
		}
		if (ilya_clicked(self::DFL_BUTTON)) {
			$this->init_gallery(self::FIELD_COUNT_MAX);
			foreach ($this->gallery as $key => $gallery) {
				ilya_opt(self::FIELD_ACTIVE.$key, (int)$gallery['active']);
				ilya_opt(self::FIELD_PROMPT.$key, $gallery['prompt']);
				ilya_opt(self::FIELD_NOTE.$key, $gallery['note']);
				ilya_opt(self::FIELD_TYPE.$key, $gallery['type']);
				ilya_opt(self::FIELD_OPTION.$key, $gallery['option']);
				ilya_opt(self::FIELD_ATTR.$key, $gallery['attr']);
				ilya_opt(self::FIELD_DEFAULT.$key, $gallery['default']);
				ilya_opt(self::FIELD_FORM_POS.$key, $gallery['form_pos']);
				ilya_opt(self::FIELD_DISPLAY.$key, (int)$gallery['display']);
				ilya_opt(self::FIELD_LABEL.$key, $gallery['label']);
				ilya_opt(self::FIELD_PAGE_POS.$key, $gallery['page_pos']);
				ilya_opt(self::FIELD_HIDE_BLANK.$key, (int)$gallery['displayblank']);
				ilya_opt(self::FIELD_REQUIRED.$key, (int)$gallery['required']);
			}
			$this->gallery_count = self::FIELD_COUNT_DFL;
			ilya_opt(self::FIELD_COUNT,$this->gallery_count);
			$this->init_gallery($this->gallery_count);
			$this->gallery_maxfile_size = self::MAXFILE_SIZE_DFL;
			$this->gallery_only_image = self::ONLY_IMAGE_DFL;
			$this->gallery_image_maxwidth = self::IMAGE_MAXWIDTH_DFL;
			$this->gallery_image_maxheight = self::IMAGE_MAXHEIGHT_DFL;
			$this->gallery_thumb_size = self::THUMB_SIZE_DFL;
			$this->gallery_lightbox_effect = self::LIGHTBOX_EFFECT_DFL;
			ilya_opt(self::THUMB_SIZE,$this->gallery_thumb_size);
			$saved = ilya_lang_html(self::PLUGIN.'/'.self::RESET_MESSAGE);
		}
		if ($saved == '' && !$error) {
			$this->gallery_count = ilya_opt(self::FIELD_COUNT);
			if(!is_numeric($this->gallery_count))
				$this->gallery_count = self::FIELD_COUNT_DFL;
			$this->init_gallery($this->gallery_count);
			$this->gallery_maxfile_size = ilya_opt(self::MAXFILE_SIZE);
			if(!is_numeric($this->gallery_maxfile_size))
				$this->gallery_maxfile_size = self::MAXFILE_SIZE_DFL;
			$this->gallery_image_maxwidth = ilya_opt(self::IMAGE_MAXWIDTH);
			if(!is_numeric($this->gallery_image_maxwidth))
				$this->gallery_image_maxwidth = self::IMAGE_MAXWIDTH_DFL;
			$this->gallery_image_maxheight = ilya_opt(self::IMAGE_MAXHEIGHT);
			if(!is_numeric($this->gallery_image_maxheight))
				$this->gallery_image_maxheight = self::IMAGE_MAXHEIGHT_DFL;
			$this->gallery_thumb_size = ilya_opt(self::THUMB_SIZE);
			if(!is_numeric($this->gallery_thumb_size))
				$this->gallery_thumb_size = self::THUMB_SIZE_DFL;
		}
		$rules = array();
		foreach ($this->gallery as $key => $gallery) {
			$rules[self::FIELD_PROMPT.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_NOTE.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_TYPE.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_OPTION.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_ATTR.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_DEFAULT.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_FORM_POS.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_DISPLAY.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_LABEL.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_PAGE_POS.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_HIDE_BLANK.$key] = self::FIELD_ACTIVE.'_field'.$key;
			$rules[self::FIELD_REQUIRED.$key] = self::FIELD_ACTIVE.'_field'.$key;
		}
		ilya_set_display_rules($ilya_content, $rules);

		$ret = array();
		if($saved != '' && !$error)
			$ret['ok'] = $saved;

		$fields = array();
		$fieldoption = array();
		for($i=self::FIELD_COUNT_DFL;$i<=self::FIELD_COUNT_MAX;$i++) {
			$fieldoption[(string)$i] = (string)$i;
		}
		$fields[] = array(
			'id' => self::FIELD_COUNT,
			'label' => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_COUNT.'_label'),
			'value' => ilya_opt(self::FIELD_COUNT),
			'tags' => 'NAME="'.self::FIELD_COUNT.'_field"',
			'type' => 'select',
			'options' => $fieldoption,
			'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_COUNT.'_note'),
		);
		$fields[] = array(
			'id' => self::MAXFILE_SIZE,
			'label' => ilya_lang_html(self::PLUGIN.'/'.self::MAXFILE_SIZE.'_label'),
			'value' => ilya_opt(self::MAXFILE_SIZE),
			'tags' => 'NAME="'.self::MAXFILE_SIZE.'_field"',
			'type' => 'number',
			'suffix' => 'bytes',
			'note' => ilya_lang(self::PLUGIN.'/'.self::MAXFILE_SIZE.'_note'),
		);
		$fields[] = array(
			'id' => self::ONLY_IMAGE,
			'label' => ilya_lang_html(self::PLUGIN.'/'.self::ONLY_IMAGE.'_label'),
			'type' => 'checkbox',
			'value' => (int)ilya_opt(self::ONLY_IMAGE),
			'tags' => 'NAME="'.self::ONLY_IMAGE.'_field"',
		);
		$fields[] = array(
			'id' => self::IMAGE_MAXWIDTH,
			'label' => ilya_lang_html(self::PLUGIN.'/'.self::IMAGE_MAXWIDTH.'_label'),
			'value' => ilya_opt(self::IMAGE_MAXWIDTH),
			'tags' => 'NAME="'.self::IMAGE_MAXWIDTH.'_field"',
			'type' => 'number',
			'suffix' => ilya_lang_html('admin/pixels'),
			'note' => ilya_lang(self::PLUGIN.'/'.self::IMAGE_MAXWIDTH.'_note'),
		);
		$fields[] = array(
			'id' => self::IMAGE_MAXHEIGHT,
			'label' => ilya_lang_html(self::PLUGIN.'/'.self::IMAGE_MAXHEIGHT.'_label'),
			'value' => ilya_opt(self::IMAGE_MAXHEIGHT),
			'tags' => 'NAME="'.self::IMAGE_MAXHEIGHT.'_field"',
			'type' => 'number',
			'suffix' => ilya_lang_html('admin/pixels'),
			'note' => ilya_lang(self::PLUGIN.'/'.self::IMAGE_MAXHEIGHT.'_note'),
		);
		$fields[] = array(
			'id' => self::THUMB_SIZE,
			'label' => ilya_lang_html(self::PLUGIN.'/'.self::THUMB_SIZE.'_label'),
			'value' => ilya_opt(self::THUMB_SIZE),
			'tags' => 'NAME="'.self::THUMB_SIZE.'_field"',
			'type' => 'number',
			'suffix' => ilya_lang_html('admin/pixels'),
			'note' => ilya_lang(self::PLUGIN.'/'.self::THUMB_SIZE.'_note'),
		);
		$fields[] = array(
			'id' => self::LIGHTBOX_EFFECT,
			'label' => ilya_lang_html(self::PLUGIN.'/'.self::LIGHTBOX_EFFECT.'_label'),
			'type' => 'checkbox',
			'value' => (int)ilya_opt(self::LIGHTBOX_EFFECT),
			'tags' => 'NAME="'.self::LIGHTBOX_EFFECT.'_field"',
		);
		$type = array(self::FIELD_TYPE_TEXT => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_TYPE_TEXT_LABEL)
					, self::FIELD_TYPE_TEXTAREA => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_TYPE_TEXTAREA_LABEL)
					, self::FIELD_TYPE_CHECK => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_TYPE_CHECK_LABEL)
					, self::FIELD_TYPE_SELECT => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_TYPE_SELECT_LABEL)
					, self::FIELD_TYPE_RADIO => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_TYPE_RADIO_LABEL)
					, self::FIELD_TYPE_FILE => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_TYPE_FILE_LABEL)
		);

		$form_pos = array();
		$form_pos[self::FIELD_FORM_POS_TOP] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_TOP_LABEL);
		if(ilya_opt('show_custom_ask'))
			$form_pos[self::FIELD_FORM_POS_CUSTOM] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_CUSTOM_LABEL);
		$form_pos[self::FIELD_FORM_POS_TITLE] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_TITLE_LABEL);
		if (ilya_using_categories())
			$form_pos[self::FIELD_FORM_POS_CATEGORY] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_CATEGORY_LABEL);
		$form_pos[self::FIELD_FORM_POS_CONTENT] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_CONTENT_LABEL);
		if (ilya_opt('gallery_active'))
			$form_pos[self::FIELD_FORM_POS_EXTRA] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_EXTRA_LABEL);
		if (ilya_using_tags())
			$form_pos[self::FIELD_FORM_POS_TAGS] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_TAGS_LABEL);
		$form_pos[self::FIELD_FORM_POS_NOTIFY] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_NOTIFY_LABEL);
		$form_pos[self::FIELD_FORM_POS_BOTTOM] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_BOTTOM_LABEL);

		$page_pos = array(self::FIELD_PAGE_POS_UPPER => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_PAGE_POS_UPPER_LABEL)
						, self::FIELD_PAGE_POS_INSIDE => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_PAGE_POS_INSIDE_LABEL)
						, self::FIELD_PAGE_POS_BELOW => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_PAGE_POS_BELOW_LABEL)
		);
		
		foreach ($this->gallery as $key => $gallery) {
			$fields[] = array(
				'id' => self::FIELD_ACTIVE.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_ACTIVE.'_label',$key),
				'type' => 'checkbox',
				'value' => (int)ilya_opt(self::FIELD_ACTIVE.$key),
				'tags' => 'NAME="'.self::FIELD_ACTIVE.'_field'.$key.'" ID="'.self::FIELD_ACTIVE.'_field'.$key.'"',
				'error' => $error_active[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_PROMPT.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_PROMPT.'_label',$key),
				'value' => ilya_html(ilya_opt(self::FIELD_PROMPT.$key)),
				'tags' => 'NAME="'.self::FIELD_PROMPT.'_field'.$key.'" ID="'.self::FIELD_PROMPT.'_field'.$key.'"',
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_PROMPT.'_note',$key),
				'error' => $error_prompt[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_NOTE.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_NOTE.'_label',$key),
				'type' => 'textarea',
				'value' => ilya_opt(self::FIELD_NOTE.$key),
				'tags' => 'NAME="'.self::FIELD_NOTE.'_field'.$key.'" ID="'.self::FIELD_NOTE.'_field'.$key.'"',
				'rows' => $this->gallery_note_height,
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_NOTE.'_note',$key),
				'error' => $error_note[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_TYPE.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_TYPE.'_label',$key),
				'tags' => 'NAME="'.self::FIELD_TYPE.'_field'.$key.'" ID="'.self::FIELD_TYPE.'_field'.$key.'"',
				'type' => 'select',
				'options' => $type,
				'value' => @$type[ilya_opt(self::FIELD_TYPE.$key)],
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_TYPE.'_note',$key),
				'error' => $error_type[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_OPTION.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_OPTION.'_label',$key),
				'type' => 'textarea',
				'value' => ilya_opt(self::FIELD_OPTION.$key),
				'tags' => 'NAME="'.self::FIELD_OPTION.'_field'.$key.'" ID="'.self::FIELD_OPTION.'_field'.$key.'"',
				'rows' => $this->gallery_option_height,
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_OPTION.'_note',$key),
				'error' => $error_option[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_ATTR.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_ATTR.'_label',$key),
				'value' => ilya_html(ilya_opt(self::FIELD_ATTR.$key)),
				'tags' => 'NAME="'.self::FIELD_ATTR.'_field'.$key.'" ID="'.self::FIELD_ATTR.'_field'.$key.'"',
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_ATTR.'_note',$key),
				'error' => $error_attr[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_DEFAULT.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_DEFAULT.'_label',$key),
				'value' => ilya_html(ilya_opt(self::FIELD_DEFAULT.$key)),
				'tags' => 'NAME="'.self::FIELD_DEFAULT.'_field'.$key.'" ID="'.self::FIELD_DEFAULT.'_field'.$key.'"',
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_DEFAULT.'_note',$key),
				'error' => $error_default[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_FORM_POS.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_FORM_POS.'_label',$key),
				'tags' => 'NAME="'.self::FIELD_FORM_POS.'_field'.$key.'" ID="'.self::FIELD_FORM_POS.'_field'.$key.'"',
				'type' => 'select',
				'options' => $form_pos,
				'value' => @$form_pos[ilya_opt(self::FIELD_FORM_POS.$key)],
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_FORM_POS.'_note',$key),
				'error' => $error_form_pos[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_DISPLAY.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_DISPLAY.'_label',$key),
				'type' => 'checkbox',
				'value' => (int)ilya_opt(self::FIELD_DISPLAY.$key),
				'tags' => 'NAME="'.self::FIELD_DISPLAY.'_field'.$key.'" ID="'.self::FIELD_DISPLAY.'_field'.$key.'"',
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_DISPLAY.'_note',$key),
				'error' => $error_display[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_LABEL.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_LABEL.'_label',$key),
				'value' => ilya_html(ilya_opt(self::FIELD_LABEL.$key)),
				'tags' => 'NAME="'.self::FIELD_LABEL.'_field'.$key.'" ID="'.self::FIELD_LABEL.'_field'.$key.'"',
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_LABEL.'_note',$key),
				'error' => $error_label[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_PAGE_POS.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_PAGE_POS.'_label',$key),
				'tags' => 'NAME="'.self::FIELD_PAGE_POS.'_field'.$key.'" ID="'.self::FIELD_PAGE_POS.'_field'.$key.'"',
				'type' => 'select',
				'options' => $page_pos,
				'value' => @$page_pos[ilya_opt(self::FIELD_PAGE_POS.$key)],
				'note' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_PAGE_POS.'_note',str_replace('^', $key, self::FIELD_PAGE_POS_HOOK)),
				'error' => $error_page_pos[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_HIDE_BLANK.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_HIDE_BLANK.'_label',$key),
				'type' => 'checkbox',
				'value' => (int)ilya_opt(self::FIELD_HIDE_BLANK.$key),
				'tags' => 'NAME="'.self::FIELD_HIDE_BLANK.'_field'.$key.'" ID="'.self::FIELD_HIDE_BLANK.'_field'.$key.'"',
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_HIDE_BLANK.'_note',$key),
				'error' => $error_hide_blank[$key],
			);
			$fields[] = array(
				'id' => self::FIELD_REQUIRED.$key,
				'label' => ilya_lang_html_sub(self::PLUGIN.'/'.self::FIELD_REQUIRED.'_label',$key),
				'type' => 'checkbox',
				'value' => (int)ilya_opt(self::FIELD_REQUIRED.$key),
				'tags' => 'NAME="'.self::FIELD_REQUIRED.'_field'.$key.'" ID="'.self::FIELD_REQUIRED.'_field'.$key.'"',
				'note' => ilya_lang(self::PLUGIN.'/'.self::FIELD_REQUIRED.'_note',$key),
				'error' => $error_required[$key],
			);
		}
		$ret['fields'] = $fields;
		
		$buttons = array();
		$buttons[] = array(
			'label' => ilya_lang_html(self::PLUGIN.'/'.self::SAVE_BUTTON),
			'tags' => 'NAME="'.self::SAVE_BUTTON.'" ID="'.self::SAVE_BUTTON.'"',
		);
		$buttons[] = array(
			'label' => ilya_lang_html(self::PLUGIN.'/'.self::DFL_BUTTON),
			'tags' => 'NAME="'.self::DFL_BUTTON.'" ID="'.self::DFL_BUTTON.'"',
		);
		$ret['buttons'] = $buttons;

		return $ret;
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/