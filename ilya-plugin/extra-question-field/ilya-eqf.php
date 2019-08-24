<?php
if (!defined('ILYA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
class ilya_eqf {
	const PLUGIN						= 'extra_field';
	const FIELD_BASE_NAME				= 'extra';
	const FIELD_COUNT					= 'extra_field_count';
	const FIELD_COUNT_DFL				= 3;
	const FIELD_COUNT_MAX				= 20;
	const MAXFILE_SIZE					= 'extra_field_maxfile_size';
	const MAXFILE_SIZE_DFL				= 2097152;
	const ONLY_IMAGE					= 'extra_field_only_image';
	const ONLY_IMAGE_DFL				= false;	// Can't change true.
	const IMAGE_MAXWIDTH				= 'extra_field_image_maxwidth';
	const IMAGE_MAXWIDTH_DFL			= 600;
	const IMAGE_MAXHEIGHT				= 'extra_field_image_maxheight';
	const IMAGE_MAXHEIGHT_DFL			= 600;
	const THUMB_SIZE					= 'extra_field_thumb_size';
	const THUMB_SIZE_DFL				= 100;
	const LIGHTBOX_EFFECT				= 'extra_field_lightbox_effect';
	const LIGHTBOX_EFFECT_DFL			= false;	// Can't change true.
	const FIELD_ACTIVE					= 'extra_field_active';
	const FIELD_ACTIVE_DFL				= false;	// Can't change true.
	const FIELD_PROMPT					= 'extra_field_prompt';
	const FIELD_NOTE					= 'extra_field_note';
	const FIELD_NOTE_HEIGHT				= 2;
	const FIELD_TYPE					= 'extra_field_type';
	const FIELD_TYPE_DFL				= 'text';
	const FIELD_TYPE_TEXT				= 'text';
	const FIELD_TYPE_TEXT_LABEL			= 'extra_field_type_text';
	const FIELD_TYPE_TEXTAREA			= 'textarea';
	const FIELD_TYPE_TEXTAREA_LABEL		= 'extra_field_type_textarea';
	const FIELD_TYPE_CHECK				= 'checkbox';
	const FIELD_TYPE_CHECK_LABEL		= 'extra_field_type_checkbox';
	const FIELD_TYPE_SELECT				= 'select';
	const FIELD_TYPE_SELECT_LABEL		= 'extra_field_type_select';
	const FIELD_TYPE_RADIO				= 'select-radio';
	const FIELD_TYPE_RADIO_LABEL		= 'extra_field_type_radio';
	const FIELD_TYPE_FILE				= 'file';
	const FIELD_TYPE_FILE_LABEL			= 'extra_field_type_file';
	const FIELD_OPTION					= 'extra_field_option';
	const FIELD_OPTION_HEIGHT			= 2;
	const FIELD_OPTION_ROWS_DFL			= 3;
	const FIELD_OPTION_EXT_ERROR		= 'extra_field_option_ext_error';
	const FIELD_ATTR					= 'extra_field_attr';
	const FIELD_DEFAULT					= 'extra_field_default';
	const FIELD_FORM_POS				= 'extra_field_form_pos';
	const FIELD_FORM_POS_DFL			= 'content';
	const FIELD_FORM_POS_TOP			= 'top';
	const FIELD_FORM_POS_TOP_LABEL		= 'extra_field_form_pos_top';
	const FIELD_FORM_POS_CUSTOM			= 'custom';
	const FIELD_FORM_POS_CUSTOM_LABEL	= 'extra_field_form_pos_custom';
	const FIELD_FORM_POS_TITLE			= 'title';
	const FIELD_FORM_POS_TITLE_LABEL	= 'extra_field_form_pos_title';
	const FIELD_FORM_POS_CATEGORY		= 'category';
	const FIELD_FORM_POS_CATEGORY_LABEL	= 'extra_field_form_pos_category';
	const FIELD_FORM_POS_CONTENT		= 'content';
	const FIELD_FORM_POS_CONTENT_LABEL	= 'extra_field_form_pos_content';
	const FIELD_FORM_POS_EXTRA			= 'extra';
	const FIELD_FORM_POS_EXTRA_LABEL	= 'extra_field_form_pos_extra';
	const FIELD_FORM_POS_TAGS			= 'tags';
	const FIELD_FORM_POS_TAGS_LABEL		= 'extra_field_form_pos_tags';
	const FIELD_FORM_POS_NOTIFY			= 'notify';
	const FIELD_FORM_POS_NOTIFY_LABEL	= 'extra_field_form_pos_notify';
	const FIELD_FORM_POS_BOTTOM			= 'bottom';
	const FIELD_FORM_POS_BOTTOM_LABEL	= 'extra_field_form_pos_bottom';
	const FIELD_DISPLAY					= 'extra_field_display';
	const FIELD_DISPLAY_DFL				= false;	// Can't change true.
	const FIELD_LABEL					= 'extra_field_label';
	const FIELD_PAGE_POS				= 'extra_field_page_pos';
	const FIELD_PAGE_POS_DFL			= 'below';
	const FIELD_PAGE_POS_UPPER			= 'upper';
	const FIELD_PAGE_POS_UPPER_LABEL	= 'extra_field_page_pos_upper';
	const FIELD_PAGE_POS_INSIDE			= 'inside';
	const FIELD_PAGE_POS_INSIDE_LABEL	= 'extra_field_page_pos_inside';
	const FIELD_PAGE_POS_BELOW			= 'below';
	const FIELD_PAGE_POS_BELOW_LABEL	= 'extra_field_page_pos_below';
	const FIELD_PAGE_POS_HOOK			= '[*attachment^*]';
	const FIELD_HIDE_BLANK				= 'extra_field_hide_blank';
	const FIELD_HIDE_BLANK_DFL			= false;	// Can't change true.
	const FIELD_REQUIRED				= 'extra_field_required';
	const FIELD_REQUIRED_DFL			= false;	// Can't change true.
	const SAVE_BUTTON					= 'extra_field_save_button';
	const DFL_BUTTON					= 'extra_field_dfl_button';
	const SAVED_MESSAGE					= 'extra_field_saved_message';
	const RESET_MESSAGE					= 'extra_field_reset_message';
	
	var $directory;
	var $urltoroot;

	var $extra_field_count;
	var $extra_field_maxfile_size;
	var $extra_field_only_image;
	var $extra_field_image_maxwidth;
	var $extra_field_image_maxheight;
	var $extra_field_thumb_size;
	var $extra_field_lightbox_effect;
	var $extra_fields;
	var $extra_field_note_height;
	var $extra_field_option_height;

	function __construct() {
		$this->extra_field_count = self::FIELD_COUNT_DFL;
		$this->extra_field_maxfile_size = self::MAXFILE_SIZE_DFL;
		$this->extra_field_only_image = self::ONLY_IMAGE_DFL;
		$this->extra_field_image_maxwidth = self::IMAGE_MAXWIDTH_DFL;
		$this->extra_field_image_maxheight = self::IMAGE_MAXHEIGHT_DFL;
		$this->extra_field_thumb_size = self::THUMB_SIZE_DFL;
		$this->extra_field_lightbox_effect = self::LIGHTBOX_EFFECT_DFL;
		$this->init_extra_fields($this->extra_field_count);
		$this->extra_field_note_height = self::FIELD_NOTE_HEIGHT;
		$this->extra_field_option_height = self::FIELD_OPTION_HEIGHT;
	}
	function init_extra_fields($count) {
		$this->extra_fields = array();
		for($key=1; $key<=$count; $key++) {
			$this->extra_fields[(string)$key] = array(
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
		if ($option==self::FIELD_COUNT) return $this->extra_field_count;
		if ($option==self::MAXFILE_SIZE) return $this->extra_field_maxfile_size;
		if ($option==self::ONLY_IMAGE) return $this->extra_field_only_image;
		if ($option==self::IMAGE_MAXWIDTH) return $this->extra_field_image_maxwidth;
		if ($option==self::IMAGE_MAXHEIGHT) return $this->extra_field_image_maxheight;
		if ($option==self::THUMB_SIZE) return $this->extra_field_thumb_size;
		if ($option==self::LIGHTBOX_EFFECT) return $this->extra_field_lightbox_effect;
		foreach ($this->extra_fields as $key => $extra_field) {
			if ($option==self::FIELD_ACTIVE.$key) return $extra_field['active'];
			if ($option==self::FIELD_PROMPT.$key) return $extra_field['prompt'];
			if ($option==self::FIELD_NOTE.$key) return $extra_field['note'];
			if ($option==self::FIELD_TYPE.$key) return $extra_field['type'];
			if ($option==self::FIELD_OPTION.$key) return $extra_field['option'];
			if ($option==self::FIELD_ATTR.$key) return $extra_field['attr'];
			if ($option==self::FIELD_DEFAULT.$key) return $extra_field['default'];
			if ($option==self::FIELD_FORM_POS.$key) return $extra_field['form_pos'];
			if ($option==self::FIELD_DISPLAY.$key) return $extra_field['display'];
			if ($option==self::FIELD_LABEL.$key) return $extra_field['label'];
			if ($option==self::FIELD_PAGE_POS.$key) return $extra_field['page_pos'];
			if ($option==self::FIELD_HIDE_BLANK.$key) return $extra_field['displayblank'];
			if ($option==self::FIELD_REQUIRED.$key) return $extra_field['required'];
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
			$this->init_extra_fields(ilya_post_text(self::FIELD_COUNT.'_field'));
			foreach ($this->extra_fields as $key => $extra_field) {
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
			foreach ($this->extra_fields as $key => $extra_field) {
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
			$this->init_extra_fields(self::FIELD_COUNT_MAX);
			foreach ($this->extra_fields as $key => $extra_field) {
				ilya_opt(self::FIELD_ACTIVE.$key, (int)$extra_field['active']);
				ilya_opt(self::FIELD_PROMPT.$key, $extra_field['prompt']);
				ilya_opt(self::FIELD_NOTE.$key, $extra_field['note']);
				ilya_opt(self::FIELD_TYPE.$key, $extra_field['type']);
				ilya_opt(self::FIELD_OPTION.$key, $extra_field['option']);
				ilya_opt(self::FIELD_ATTR.$key, $extra_field['attr']);
				ilya_opt(self::FIELD_DEFAULT.$key, $extra_field['default']);
				ilya_opt(self::FIELD_FORM_POS.$key, $extra_field['form_pos']);
				ilya_opt(self::FIELD_DISPLAY.$key, (int)$extra_field['display']);
				ilya_opt(self::FIELD_LABEL.$key, $extra_field['label']);
				ilya_opt(self::FIELD_PAGE_POS.$key, $extra_field['page_pos']);
				ilya_opt(self::FIELD_HIDE_BLANK.$key, (int)$extra_field['displayblank']);
				ilya_opt(self::FIELD_REQUIRED.$key, (int)$extra_field['required']);
			}
			$this->extra_field_count = self::FIELD_COUNT_DFL;
			ilya_opt(self::FIELD_COUNT,$this->extra_field_count);
			$this->init_extra_fields($this->extra_field_count);
			$this->extra_field_maxfile_size = self::MAXFILE_SIZE_DFL;
			$this->extra_field_only_image = self::ONLY_IMAGE_DFL;
			$this->extra_field_image_maxwidth = self::IMAGE_MAXWIDTH_DFL;
			$this->extra_field_image_maxheight = self::IMAGE_MAXHEIGHT_DFL;
			$this->extra_field_thumb_size = self::THUMB_SIZE_DFL;
			$this->extra_field_lightbox_effect = self::LIGHTBOX_EFFECT_DFL;
			ilya_opt(self::THUMB_SIZE,$this->extra_field_thumb_size);
			$saved = ilya_lang_html(self::PLUGIN.'/'.self::RESET_MESSAGE);
		}
		if ($saved == '' && !$error) {
			$this->extra_field_count = ilya_opt(self::FIELD_COUNT);
			if(!is_numeric($this->extra_field_count))
				$this->extra_field_count = self::FIELD_COUNT_DFL;
			$this->init_extra_fields($this->extra_field_count);
			$this->extra_field_maxfile_size = ilya_opt(self::MAXFILE_SIZE);
			if(!is_numeric($this->extra_field_maxfile_size))
				$this->extra_field_maxfile_size = self::MAXFILE_SIZE_DFL;
			$this->extra_field_image_maxwidth = ilya_opt(self::IMAGE_MAXWIDTH);
			if(!is_numeric($this->extra_field_image_maxwidth))
				$this->extra_field_image_maxwidth = self::IMAGE_MAXWIDTH_DFL;
			$this->extra_field_image_maxheight = ilya_opt(self::IMAGE_MAXHEIGHT);
			if(!is_numeric($this->extra_field_image_maxheight))
				$this->extra_field_image_maxheight = self::IMAGE_MAXHEIGHT_DFL;
			$this->extra_field_thumb_size = ilya_opt(self::THUMB_SIZE);
			if(!is_numeric($this->extra_field_thumb_size))
				$this->extra_field_thumb_size = self::THUMB_SIZE_DFL;
		}
		$rules = array();
		foreach ($this->extra_fields as $key => $extra_field) {
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
		if (ilya_opt('extra_field_active'))
			$form_pos[self::FIELD_FORM_POS_EXTRA] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_EXTRA_LABEL);
		if (ilya_using_tags())
			$form_pos[self::FIELD_FORM_POS_TAGS] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_TAGS_LABEL);
		$form_pos[self::FIELD_FORM_POS_NOTIFY] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_NOTIFY_LABEL);
		$form_pos[self::FIELD_FORM_POS_BOTTOM] = ilya_lang_html(self::PLUGIN.'/'.self::FIELD_FORM_POS_BOTTOM_LABEL);

		$page_pos = array(self::FIELD_PAGE_POS_UPPER => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_PAGE_POS_UPPER_LABEL)
						, self::FIELD_PAGE_POS_INSIDE => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_PAGE_POS_INSIDE_LABEL)
						, self::FIELD_PAGE_POS_BELOW => ilya_lang_html(self::PLUGIN.'/'.self::FIELD_PAGE_POS_BELOW_LABEL)
		);
		
		foreach ($this->extra_fields as $key => $extra_field) {
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
				'rows' => $this->extra_field_note_height,
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
				'rows' => $this->extra_field_option_height,
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