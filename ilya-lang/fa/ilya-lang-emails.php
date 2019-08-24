<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: ilya-include/ilya-lang-emails.php
	Version: See define()s at top of ilya-include/ilya-base.php
	Description: Language phrases for email notifications


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

	return array(
		'a_commented_body' => "پاسخ شما در ^site_title از سوی کاربر ^c_handle:\n\n^open^c_content^close\n\nاست was:\n\n^open^c_context^close\n\nشما ممکن است بخواهید با دیدگاه خود پاسخ دهید\n\n^url\n\nبا تشکر,\n\n^site_title",
		'a_commented_subject' => 'پاسخ شما در ^site_title یک دیدگاه جدید دارد',

		'a_followed_body' => "پاسخ شما در ^site_title دارای یک مطلب مشابه جدید است ^q_handle:\n\n^open^q_title^close\n\nپاسخ شما :\n\n^open^a_content^close\n\nبرای پاسخ به این سوال جدید بر روی لینک زیر کلیک نمایید\n\n^url\n\nبا تشکر,\n\n^site_title",
		'a_followed_subject' => 'پاسخ شما در ^site_title دارای یک مطلب مشابه است',

		'a_selected_body' => "پاسخ شما در ^site_title توسط ^s_handle به عنوان بهترین پاسخ انتخاب شده است:\n\n^open^a_content^close\n\nمطلب مربوطه:\n\n^open^q_title^close\n\nبرای دیدن پاسخ خود بر روی لینک مقابل کلیک نمایید:\n\n^url\n\nبا تشکر,\n\n^site_title",
		'a_selected_subject' => 'پاسخ شما در ^site_title به عنوان بهترین پاسخانتخاب شده است!',

		'c_commented_body' => "یک دیدگاه جدید توسط ^c_handle پس از دیدگاه شما در سایت ^site_title منتشر شده است :\n\n^open^c_content^close\n\nبحث مربوطه:\n\n^open^c_context^close\n\nبرای پاسخ دادن با دیدگاهی جدید بر روی لینک مقابل کلیک نمایید\n\n^url\n\nبا تشکر,\n\n^site_title",
		'c_commented_subject' => 'دیدگاه جدیدی پس از دیدگاه شما در ^site_title ثبت شده است',

		'confirm_body' => "لطفا بر روی لینک زیر کلیک کنید تا ایمیل شما تایید شود. از طرف ^site_title.\n\n^url\n\nبا تشکر,\n^site_title",
		'confirm_subject' => '^site_title - تایید آدرس ایمیل',

		'feedback_body' => "پیام:\n^message\n\nنام:\n^name\n\nایمیل:\n^email\n\nصفحع قبلی:\n^previous\n\nنام کاربری:\n^url\n\nآدرس IP\n^ip\n\nمرورگر\n^browser",
		'feedback_subject' => '^ پشتیبانی',

			'flagged_body' => "یک پست توسط ^p_handle دریافت شده است ^flags:\n\n^open^p_context^close\n\nبرای نمایش این پست بر روی لینک مقابل کلیک نمایید:\n\n^url\n\n\nبرای نمایش تمام پست های نشانه گذاری شده بر  روی لینک مقابل کلیک نمایید:\n\n^a_url\n\n\nبا تشکر,\n\n^site_title",
		'flagged_subject' => 'در ^site_title یک پست به عنوان اسپم نشانه گذاری شده!',

		'moderate_body' => "یک پست در ^p_handle در انتظار تایید شماست:\n\n^open^p_context^close\n\nبرای تایید یا ردکردن پست بر روی لینک روبرو کلیک نمایید:\n\n^url\n\n\nبرای نمایش تممام این پست ها بر روی لینک مقابل کلیک نمایید:\n\n^a_url\n\n\nبا تشکر,\n\n^site_title",
		'moderate_subject' => '^site_title تایید پست در',

		'new_password_body' => "رمز عبور جدید شما برای ^site_title اینگونه است.\n\nرمز عبور : ^password\n\nIt لطفا پس از ورود به سایت این رمز را تغییر دهید\n\nبا تشکر,\n^site_title\n^url",
		'new_password_subject' => '^site_title - رمز عبور جدید',

		'private_message_body' => "یک پیام خصوصی از سوی ^f_handle در ^site_title برای شما ارسال شده است:\n\n^open^message^close\n\n^moreبا تشکر,\n\n^site_title\n\n\nبرای مسدود کردن پیام خصوصی از لینک رو برو به پروفایل خود مراجعه نمایید:\n^a_url",
		'private_message_info' => "اطلاعات بیشتر در مورد ^f_handle:\n\n^url\n\n",
		'private_message_reply' => "برای پاسخ به کاربر ^f_handle توسط پیام خصوصی بر روی لینک مقابل کلیک نمایید :\n\n^url\n\n",
		'private_message_subject' => 'پیامی از ^f_handle در ^site_title',

		'q_answered_body' => "سوال شما در ^site_title دارای پاسخی از سوی ^a_handle:\n\n^open^a_content^close\n\nمی باشد - سوال شما:\n\n^open^q_title^close\n\nاگر این پاسخ مناسب است با کلیک بر روی لینک مقابل آن را به عنوان بهترین سوال انتخاب کنید :\n\n^url\n\nبا تشکر,\n\n^site_title",
		'q_answered_subject' => 'مطلب شما در ^site_title دارای پاسخ جدیدی است',

		'q_commented_body' => "سوال شما در ^site_title دارای دیدگاه جدید از ^c_handle:\n\n^open^c_content^close\n\n است ,مطلب شما:\n\n^open^c_context^close\n\nبرای پاسخ به این دیدگاه بر روی لینک روبرو کلیک نمایید:\n\n^url\n\nبا تشکر,\n\n^site_title",
		'q_commented_subject' => 'مطلب شما در ^site_title دارای دیدگاه جدیدی است',

		'q_posted_body' => "یک سوال جدید توسط کاربر : ^q_handle:\n\n^open^q_title\n\n^q_content^close\n\nپرسیده شده است - برای نمایش مطلب بر روی لینک مقابل کلیک کنید :\n\n^url\n\nبا تشکر,\n\n^site_title",
		'q_posted_subject' => '^site_title دارای مطلب جدیدی است',
		
		'remoderate_body' => "یک پست توسط ^p_handle ویرایش شده است و در انتظار تایید شماست:\n\n^open^p_context^close\n\nبرای تایید یا مخفی سازی این پست ویرایش شده بر روی لینک مقابل کلیک نمایید:\n\n^url\n\n\nبرای نمایش صف پست ها بر روی لینک مقابل کلیک نمایید:\n\n^a_url\n\n\nبا تشکر,\n\n^site_title",
		'remoderate_subject' => '^site_title - پستی ویرایش شده است ',

		'reset_body' => "لطفا برای بازیابی رمز عبور خود بر روی لینک زیر کلیک کنید ^site_title.\n\n^url\n\nسپس کد زیر را در صفحه باز شده وارد نمایید\n\nکد: ^code\n\nاگر شما این ایمیل را فعال نکرده اید این پیام را نادیده بگیرید.\n\nبا تشکر,\n^site_title",
		'reset_subject' => '^site_title - بازیابی رمز عبور',

		'to_handle_prefix' => "^,\n\n",
		
		'u_registered_body' => "کاربری با این عنوان ثبت نام کرده است : ^u_handle.\n\nبرای نمایش پروفایل او بر روی لینک کلیک کنید :\n\n^url\n\nبا تشکر,\n\n^site_title",
		'u_to_approve_body' => "کاربری با این عنوان ثبت نام کرده است : ^u_handle.\n\nبرای تایید این کاربر بر روی لینک کلیک کنید :\n\n^url\n\nبرای نمایش تمام کاربران در انتظار تایید بر روی لینک مقابل کلیک کنید :\n\n^a_url\n\nبا تشکر,\n\n^site_title",
		'u_registered_subject' => '^site_title دارای کاربر جدیدی است',
		
		'u_approved_body' => "شما میتوانید حساب کاربری جدید را در اینجا ببینید\n\n^url\n\nبا تشکر,\n\n^site_title",
		'u_approved_subject' => 'حساب کاربری جدید در ^site_title تایید شد',
		
		'wall_post_subject' => 'پستی بر روی دیوار پروفایل شما در ^site_title ارسال شده است',
		'wall_post_body' => "^f_handle پستی در دیوار پروفایل شما ارسال کرده است ^site_title:\n\n^open^post^close\n\nشاید بخواهید در اینجا به این پست پاسخ دهید:\n\n^url\n\nبا تشکر,\n\n^site_title",

		'welcome_body' => "از اینکه در وبسایت ^site_title ثبت نام کرده اید متشکریم.\n\n^custom^confirmاطلاعات ورود شما به شرح زیر است:\n\nنام کاربری: ^handle\nایمیل: ^email\n\nلطفا این اطلاعات را در مکانی امن نگهداری کنید.\n\nبا تشکر,\n\n^site_title\n^url",
		'welcome_confirm' => "لطفا بر روی لینک زیر کلیک کنید تا ایمیل شما تایید شود\n\n^url\n\n",
		'welcome_subject' => 'خوش آمدید به وبسایت ^site_title!',
	);
	

/*
	Omit PHP closing tag to help avoid accidental output
*/