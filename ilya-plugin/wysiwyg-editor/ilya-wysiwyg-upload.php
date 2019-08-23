<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: ilya-plugin/wysiwyg-editor/ilya-wysiwyg-upload.php
	Description: Page module class for WYSIWYG editor (CKEditor) file upload receiver


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


class ilya_wysiwyg_upload
{
	public function match_request($request)
	{
		return $request === 'wysiwyg-editor-upload';
	}

	public function process_request($request)
	{
		$message = '';
		$url = '';

		if (is_array($_FILES) && count($_FILES)) {
			if (ilya_opt('wysiwyg_editor_upload_images')) {
				require_once ILYA__INCLUDE_DIR . 'app/upload.php';

				$onlyImage = ilya_get('ilya_only_image');
				$upload = ilya_upload_file_one(
					ilya_opt('wysiwyg_editor_upload_max_size'),
					$onlyImage || !ilya_opt('wysiwyg_editor_upload_all'),
					$onlyImage ? 600 : null, // max width if it's an image upload
					null // no max height
				);

				if (isset($upload['error'])) {
					$message = $upload['error'];
				} else {
					$url = $upload['bloburl'];
				}
			} else {
				$message = ilya_lang('users/no_permission');
			}
		}

		echo sprintf(
			'<script>window.parent.CKEDITOR.tools.callFunction(%s, %s, %s);</script>',
			ilya_js(ilya_get('CKEditorFuncNum')),
			ilya_js($url),
			ilya_js($message)
		);

		return null;
	}
}
