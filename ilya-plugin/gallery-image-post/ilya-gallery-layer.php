<?php

require_once ILYA_INCLUDE_DIR.'ilya-theme-base.php';
require_once ILYA_INCLUDE_DIR.'ilya-app-blobs.php';
require_once ILYA_PLUGIN_DIR.'gallery-image-post/ilya-gallery.php';

class ilya_html_theme_layer extends ilya_html_theme_base {

	private $extradata = [];
	private $pluginurl;
	
	function doctype() {
		ilya_html_theme_base::doctype();
		$this->pluginurl = ilya_opt('site_url').'ilya-plugin/gallery-image-post/';
		if($this->template == 'question') {
			if(isset($this->content['q_view']['raw']['postid']))
				$this->extradata = $this->ilya_gallery_get_extradata($this->content['q_view']['raw']['postid']);
		}
	}
	function head_script() {
		ilya_html_theme_base::head_script();
		if(count($this->extradata) && $this->ilya_gallery_file_exist() && ilya_opt(ilya_gallery::LIGHTBOX_EFFECT)) {
			$this->output('<SCRIPT TYPE="text/javascript" SRC="'.$this->pluginurl.'magnific-popup/jquery.magnific-popup.min.js"></SCRIPT>');
			$this->output('<SCRIPT TYPE="text/javascript">');
			$this->output('$(function(){');
			$this->output('	$(".ilya-q-view-extra-upper-img, .ilya-q-view-extra-inside-img, .ilya-q-view-extra-img").magnificPopup({');
			$this->output('		type: \'image\',');
			$this->output('		tError: \'<a href="%url%">The image</a> could not be loaded.\',');
			$this->output('		image: {');
			$this->output('			titleSrc: \'title\'');
			$this->output('		},');
			$this->output('		gallery: {');
			$this->output('			enabled: true');
			$this->output('		},');
			$this->output('		callbacks: {');
			$this->output('			elementParse: function(item) {');
			$this->output('				console.log(item);');
			$this->output('			}');
			$this->output('		}');
			$this->output('	});');
			$this->output('});');
			$this->output('</SCRIPT>');
		}
	}

	function head_css() {
		ilya_html_theme_base::head_css();
		if(count($this->extradata) && $this->ilya_gallery_file_exist() && ilya_opt(ilya_gallery::LIGHTBOX_EFFECT)) {
			$this->output('<LINK REL="stylesheet" TYPE="text/css" href="'.$this->pluginurl.'magnific-popup/magnific-popup.css"/>');
		}
	}

	function main() {
		if($this->template == 'ask') {
			if(isset($this->content['form']['fields']))
				$this->ilya_gallery_add_field(null, $this->content['form']['fields'], $this->content['form']);
		} else if(isset($this->content['form_q_edit']['fields'])) {
				$this->ilya_gallery_add_field($this->content['q_view']['raw']['postid'], $this->content['form_q_edit']['fields'], $this->content['form_q_edit']);
		}
		ilya_html_theme_base::main();
	}
	function q_view_content_top($q_view) {
		if(!isset($this->content['form_q_edit'])) {
		    $this->output('<div class="ilya-half">');
                $this->ilya_gallery_output($q_view, ilya_gallery::FIELD_PAGE_POS_UPPER);
                $this->ilya_gallery_output($q_view, ilya_gallery::FIELD_PAGE_POS_INSIDE);
                $this->ilya_gallery_clearhook($q_view);
            $this->output('</div>');
        }
		parent::q_view_content_top($q_view);
	}
	function q_view_extra($q_view) {
		parent::q_view_extra($q_view);

		if(!isset($this->content['form_q_edit'])) {
			$this->ilya_gallery_output($q_view, ilya_gallery::FIELD_PAGE_POS_BELOW);
		}
	}
	
	function ilya_gallery_add_field($postid, &$fields, &$form) {
		global $ilya_gallery_images;
		$multipart = false;
		for($key=ilya_gallery::FIELD_COUNT_MAX; $key>=1; $key--) {
			if((bool)ilya_opt(ilya_gallery::FIELD_ACTIVE.$key)) {
				$field = array();
				$name = ilya_gallery::FIELD_BASE_NAME.$key;
				$field['label'] = ilya_opt(ilya_gallery::FIELD_PROMPT.$key);
				$type = ilya_opt(ilya_gallery::FIELD_TYPE.$key);
				switch ($type) {
				case ilya_gallery::FIELD_TYPE_FILE:
					$field['type'] = 'custom';
					$value = ilya_db_single_select(ilya_db_post_meta_selectspec($postid, 'ilya_q_'.$name));
					$original = '';
					if(!empty($value)) {
						$blob = ilya_read_blob($value);
						$format = $blob['format'];
						$bloburl = ilya_get_blob_url($value);
						$imageurl = str_replace('ilya=blob', 'ilya=image', $bloburl);
						$filename = $blob['filename'];
						$original = $filename;
						$width = $this->ilya_gallery_get_image_width($blob['content']);
						if($width > ilya_opt(ilya_gallery::THUMB_SIZE))
							$width = ilya_opt(ilya_gallery::THUMB_SIZE);
						if($format == 'jpg' || $format == 'jpeg' || $format == 'png' || $format == 'gif')
							$original = '<IMG SRC="'.$imageurl.'&ilya_size='.$width.'" ALT="'.$filename.'" ID="'.$name.'-thumb" CLASS="'.ilya_gallery::FIELD_BASE_NAME.'-thumb"/>';
						$original = '<A HREF="'.$imageurl.'" TARGET="_blank" ID="'.$name.'-link" CLASS="'.ilya_gallery::FIELD_BASE_NAME.'-link">' . $original . '</A>';
						$original .= '<INPUT TYPE="checkbox" NAME="'.$name.'-remove" id="'.$name.'-remove" CLASS="'.ilya_gallery::FIELD_BASE_NAME.'-remove"/><label for="'.$name.'-remove">'.ilya_lang(ilya_gallery::PLUGIN.'/gallery_remove').'</label><br>';
						$original .= '<INPUT TYPE="hidden" NAME="'.$name.'-old" id="'.$name.'-old" value="'.$value.'"/>';
					}
					$field['html'] = $original.'<INPUT TYPE="file" CLASS="ilya-form-tall-'.$type.'" NAME="'.$name.'">';
					$multipart = true;
					break;
				default:
					$field['type'] = ilya_opt(ilya_gallery::FIELD_TYPE.$key);
					$field['tags'] = 'NAME="'.$name.'"';
					$options = $this->ilya_gallery_options(ilya_opt(ilya_gallery::FIELD_OPTION.$key));
					if (ilya_opt(ilya_gallery::FIELD_ATTR.$key) != '')
						$field['tags'] .= ' '.ilya_opt(ilya_gallery::FIELD_ATTR.$key);
					if ($field['type'] != ilya_gallery::FIELD_TYPE_TEXT && $field['type'] != ilya_gallery::FIELD_TYPE_TEXTAREA)
						$field['options'] = $options;
					if(is_null($postid))
						$field['value'] = ilya_opt(ilya_gallery::FIELD_DEFAULT.$key);
					else
						$field['value'] = ilya_db_single_select(ilya_db_post_meta_selectspec($postid, 'ilya_q_'.$name));
					if ($field['type'] != ilya_gallery::FIELD_TYPE_TEXT && $field['type'] != ilya_gallery::FIELD_TYPE_TEXTAREA && is_array($field['options'])) {
						if($field['type'] == ilya_gallery::FIELD_TYPE_CHECK) {
							if($field['value'] == 0)
								$field['value'] = '';
						} else
							$field['value'] = @$field['options'][$field['value']];
					}
					if ($field['type'] == ilya_gallery::FIELD_TYPE_TEXTAREA) {
						if(isset($options[0]))
							$field['rows'] = $options[0];
						if(empty($field['rows']))
							$field['rows'] = ilya_gallery::FIELD_OPTION_ROWS_DFL;
					}
					break;
				}
				$field['note'] = nl2br(ilya_opt(ilya_gallery::FIELD_NOTE.$key));
				if(isset($ilya_gallery_images[$name]['error']))
					$field['error'] = $ilya_gallery_images[$name]['error'];
				$this->ilya_gallery_insert_array($fields, $field, $name, ilya_opt(ilya_gallery::FIELD_FORM_POS.$key));
			}
		}
		if($multipart) {
			$form['tags'] .= ' enctype="multipart/form-data"';
		}
	}
	function ilya_gallery_insert_array(&$items, $insertitem, $insertkey, $findkey) {
		$newitems = array();
		if($findkey == ilya_gallery::FIELD_FORM_POS_TOP) {
			$newitems[$insertkey] = $insertitem;
			foreach($items as $key => $item)
				$newitems[$key] = $item;
		} elseif($findkey == ilya_gallery::FIELD_FORM_POS_BOTTOM) {
			foreach($items as $key => $item)
				$newitems[$key] = $item;
			$newitems[$insertkey] = $insertitem;
		} else {
			if(!array_key_exists($findkey, $items))
				$findkey = ilya_gallery::FIELD_FORM_POS_DFL;
			foreach($items as $key => $item) {
				$newitems[$key] = $item;
				if($key == $findkey)
					$newitems[$insertkey] = $insertitem;
			}
		}
		$items = $newitems;
	}
	function ilya_gallery_options($optionstr) {
		if(stripos($optionstr, '@EVAL') !== false)
			$optionstr = eval(str_ireplace('@EVAL', '', $optionstr));
		if(stripos($optionstr, '||') !== false)
			$items = explode('||',$optionstr);
		else
			$items = array($optionstr);
		$options = array();
		foreach($items as $item) {
			if(strstr($item,'==')) {
				$nameval = explode('==',$item);
				$options[$nameval[1]] = $nameval[0];
			} else
				$options[$item] = $item;
		}
		return $options;
	}
	function ilya_gallery_output(&$q_view, $position) {
		$output = '';
		$isoutput = false;
		foreach($this->extradata as $key => $item) {
			if($item['position'] == $position) {
				$name = $item['name'];
				$type = $item['type'];
				$value = $item['value'];
				
				if ($type == ilya_gallery::FIELD_TYPE_TEXTAREA)
					$value = nl2br($value);
				else if ($type == ilya_gallery::FIELD_TYPE_CHECK)
					if ($value == '')
						$value = 0;
				if ($type != ilya_gallery::FIELD_TYPE_TEXT && $type != ilya_gallery::FIELD_TYPE_TEXTAREA && $type != ilya_gallery::FIELD_TYPE_FILE) {
					$options = $this->ilya_gallery_options(ilya_opt(ilya_gallery::FIELD_OPTION.$key));
					if(is_array($options))
						$value = @$options[$value];
				}
				
				if($value == '' && ilya_opt(ilya_gallery::FIELD_HIDE_BLANK.$key))
					continue;
				
				switch ($position) {
				case ilya_gallery::FIELD_PAGE_POS_UPPER:
					$outerclass  = 'ilya-row ilya-q-view-extra-upper ilya-q-view-extra-upper'.$key;
					$innertclass = 'ilya-col s6 ilya-q-view-extra-upper-title ilya-q-view-extra-upper-title'.$key;
					$innervclass = 'ilya-col s6 ilya-left-align ilya-q-view-extra-upper-content ilya-q-view-extra-upper-content'.$key;
					$inneraclass = 'ilya-q-view-extra-upper-link ilya-q-view-extra-upper-link'.$key;
					$innericlass = 'ilya-q-view-extra-upper-img ilya-q-view-extra-upper-img'.$key;
					break;
				case ilya_gallery::FIELD_PAGE_POS_INSIDE:
					$outerclass  = 'ilya-q-view-extra-inside ilya-q-view-extra-inside'.$key;
					$innertclass = 'ilya-q-view-extra-inside-title ilya-q-view-extra-inside-title'.$key;
					$innervclass = 'ilya-q-view-extra-inside-content ilya-q-view-extra-inside-content'.$key;
					$inneraclass = 'ilya-q-view-extra-inside-link ilya-q-view-extra-inside-link'.$key;
					$innericlass = 'ilya-q-view-extra-inside-img ilya-q-view-extra-inside-img'.$key;
					break;
				case ilya_gallery::FIELD_PAGE_POS_BELOW:
					$outerclass = 'ilya-q-view-extra ilya-q-view-extra'.$key;
					$innertclass = 'ilya-q-view-extra-title ilya-q-view-extra-title'.$key;
					$innervclass = 'ilya-q-view-extra-content ilya-q-view-extra-content'.$key;
					$inneraclass = 'ilya-q-view-extra-link ilya-q-view-extra-link'.$key;
					$innericlass = 'ilya-q-view-extra-img ilya-q-view-extra-img'.$key;
					break;
				}
				$title = ilya_opt(ilya_gallery::FIELD_LABEL.$key);
				if ($type == ilya_gallery::FIELD_TYPE_FILE && $value != '') {
					if(ilya_blob_exists($value)) {
						$blob = ilya_read_blob($value);
						$format = $blob['format'];
						$bloburl = ilya_get_blob_url($value);
						$imageurl = str_replace('ilya=blob', 'ilya=image', $bloburl);
						$filename = $blob['filename'];
						$width = $this->ilya_gallery_get_image_width($blob['content']);
						if($width > ilya_opt(ilya_gallery::THUMB_SIZE))
							$width = ilya_opt(ilya_gallery::THUMB_SIZE);
						$value = $filename;
						if($format == 'jpg' || $format == 'jpeg' || $format == 'png' || $format == 'gif') {
							$value = '<IMG SRC="'.$imageurl.'&ilya_size='.$width.'" ALT="'.$filename.'" TARGET="_blank"/>';
							$value = '<A HREF="'.$imageurl.'" CLASS="'.$inneraclass.' '.$innericlass.'" TITLE="'.$title.'">' . $value . '</A>';
						} else
							$value = '<A HREF="'.$bloburl.'" CLASS="'.$inneraclass.'" TITLE="'.$title.'">' . $value . '</A>';
					} else
						$value = '';
				}
				$output .= '<DIV CLASS="'.$outerclass.'">';
				$output .= '<DIV CLASS="'.$innertclass.'">'.$title.'</DIV>';
				$output .= '<DIV CLASS="'.$innervclass.'">'.$value.'</DIV>';
				$output .= '</DIV>';
				
				if(ilya_opt(ilya_gallery::FIELD_PAGE_POS.$key) != ilya_gallery::FIELD_PAGE_POS_INSIDE)
					$this->output($output);
				else {
					if(isset($q_view['content'])) {
						$hook = str_replace('^', $key, ilya_gallery::FIELD_PAGE_POS_HOOK);
						$q_view['content'] = str_replace($hook, $output, $q_view['content']);
					}
				}
				$isoutput = true;
			}
			$output = '';
		}
		if($isoutput)
			$this->output('<DIV style="clear:both;"></DIV>');
	}
	function ilya_gallery_get_extradata($postid) {
		$extradata = array();
		for($key=1; $key<=ilya_gallery::FIELD_COUNT_MAX; $key++) {
			if((bool)ilya_opt(ilya_gallery::FIELD_ACTIVE.$key) && (bool)ilya_opt(ilya_gallery::FIELD_DISPLAY.$key)) {
				$name = ilya_gallery::FIELD_BASE_NAME.$key;
				$value = ilya_db_single_select(ilya_db_post_meta_selectspec($postid, 'ilya_q_'.$name));
				if($value == '' && ilya_opt(ilya_gallery::FIELD_HIDE_BLANK.$key))
					continue;
				$extradata[$key] = array(
					'name'=>$name,
					'type'=>ilya_opt(ilya_gallery::FIELD_TYPE.$key),
					'position'=>ilya_opt(ilya_gallery::FIELD_PAGE_POS.$key),
					'value'=>$value,
				);
			}
		}
		return $extradata;
	}
	function ilya_gallery_file_exist() {
		$fileexist = false;
		foreach($this->extradata as $key => $item) {
			if ($item['type'] == ilya_gallery::FIELD_TYPE_FILE)
				$fileexist = true;
		}
		return $fileexist;
	}
	function ilya_gallery_clearhook(&$q_view) {
		for($key=1; $key<=ilya_gallery::FIELD_COUNT_MAX; $key++) {
			if(isset($q_view['content'])) {
				$hook = str_replace('^', $key, ilya_gallery::FIELD_PAGE_POS_HOOK);
				$q_view['content'] = str_replace($hook, '', $q_view['content']);
			}
		}
	}
	function ilya_gallery_get_image_width($content) {
		$image=@imagecreatefromstring($content);
		if (is_resource($image))
			return imagesx($image);
		else
			return null;
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/