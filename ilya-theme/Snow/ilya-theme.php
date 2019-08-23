<?php

class ilya_html_theme extends ilya_html_theme_base
{
	// use new ranking layout
	protected $ranking_block_layout = true;
	protected $theme = 'snow';

	// outputs login form if user not logged in
	public function nav_user_search()
	{
		if (!ilya_is_logged_in()) {
			if (isset($this->content['navigation']['user']['login']) && !ILYA__FINAL_EXTERNAL_USERS) {
				$login = $this->content['navigation']['user']['login'];
				$this->output(
					'<form class="qam-login-form" action="'.$login['url'].'" method="post">',
						'<input type="text" class="qam-login-text" name="emailhandle" dir="auto" placeholder="'.trim(ilya_lang_html(ilya_opt('allow_login_email_only') ? 'users/email_label' : 'users/email_handle_label'), ':').'"/>',
						'<input type="password" class="qam-login-text" name="password" dir="auto" placeholder="'.trim(ilya_lang_html('users/password_label'), ':').'"/>',
						'<div class="qam-rememberbox"><input type="checkbox" name="remember" id="qam-rememberme" value="1"/>',
						'<label for="qam-rememberme" class="qam-remember">'.ilya_lang_html('users/remember').'</label></div>',
						'<input type="hidden" name="code" value="'.ilya_html(ilya_get_form_security_code('login')).'"/>',
						'<input type="submit" value="' . ilya_lang_html('users/login_button') . '" class="ilya-form-tall-button ilya-form-tall-button-login" name="dologin"/>',
					'</form>'
				);

				// remove regular navigation link to log in page
				unset($this->content['navigation']['user']['login']);
			}
		}

		ilya_html_theme_base::nav_user_search();
	}

	public function logged_in()
	{
		require_once ILYA__INCLUDE_DIR . 'app/format.php';

		if (ilya_is_logged_in()) // output user avatar to login bar
			$this->output(
				'<div class="ilya-logged-in-avatar">',
				ILYA__FINAL_EXTERNAL_USERS
				? ilya_get_external_avatar_html(ilya_get_logged_in_userid(), 24, true)
				: ilya_get_user_avatar_html(ilya_get_logged_in_flags(), ilya_get_logged_in_email(), ilya_get_logged_in_handle(),
					ilya_get_logged_in_user_field('avatarblobid'), ilya_get_logged_in_user_field('avatarwidth'), ilya_get_logged_in_user_field('avatarheight'),
					24, true),
				'</div>'
			);

		ilya_html_theme_base::logged_in();

		if (ilya_is_logged_in()) { // adds points count after logged in username
			$userpoints=ilya_get_logged_in_points();

			$pointshtml=($userpoints==1)
				? ilya_lang_html_sub('main/1_point', '1', '1')
				: ilya_lang_html_sub('main/x_points', ilya_html(ilya_format_number($userpoints)));

			$this->output(
				'<span class="ilya-logged-in-points">',
				'('.$pointshtml.')',
				'</span>'
			);
		}
	}

	// adds login bar, user navigation and search at top of page in place of custom header content
	public function body_header()
	{
		$this->output('<div class="qam-login-bar"><div class="qam-login-group">');
		$this->nav_user_search();
		$this->output('</div></div>');
	}

	// allows modification of custom element shown inside header after logo
	public function header_custom()
	{
		if (isset($this->content['body_header'])) {
			$this->output('<div class="header-banner">');
			$this->output_raw($this->content['body_header']);
			$this->output('</div>');
		}
	}

	// removes user navigation and search from header and replaces with custom header content. Also opens new <div>s
	public function header()
	{
		$this->output('<div class="ilya-header">');

		$this->logo();
		$this->header_clear();
		$this->header_custom();

		$this->output('</div> <!-- END ilya-header -->', '');

		$this->output('<div class="ilya-main-shadow">', '');
		$this->output('<div class="ilya-main-wrapper">', '');
		$this->nav_main_sub();
	}

	// removes sidebar for user profile pages
	public function sidepanel()
	{
		if ($this->template!='user')
			ilya_html_theme_base::sidepanel();
	}

	// prevent display of regular footer content (see body_suffix()) and replace with closing new <div>s
	public function footer()
	{
		$this->output('</div> <!-- END main-wrapper -->');
		$this->output('</div> <!-- END main-shadow -->');
	}

	// add RSS feed icon after the page title
	public function favorite()
	{
		parent::favorite();

		$feed = @$this->content['feed'];

		if (!empty($feed)) {
			$this->output('<a href="'.$feed['url'].'" title="'.@$feed['label'].'"><img src="'.$this->rooturl.'images/rss.jpg" alt="" width="16" height="16" class="ilya-rss-icon"/></a>');
		}
	}

	// add view count to question list
	public function q_item_stats($q_item)
	{
		$this->output('<div class="ilya-q-item-stats">');

		$this->voting($q_item);
		$this->a_count($q_item);
		ilya_html_theme_base::view_count($q_item);

		$this->output('</div>');
	}

	// prevent display of view count in the usual place
	public function view_count($q_item)
	{
		if ($this->template=='question')
			ilya_html_theme_base::view_count($q_item);
	}

	// to replace standard ILYA footer
	public function body_suffix()
	{
		$this->output('<div class="ilya-footer-bottom-group">');
		ilya_html_theme_base::footer();
		$this->output('</div> <!-- END footer-bottom-group -->', '');
	}

	public function attribution()
	{
		$this->output(
			'<div class="ilya-attribution">',
			'&nbsp;| Snow Theme by <a href="http://www.q2amarket.com">ILYA Market</a>',
			'</div>'
		);

		ilya_html_theme_base::attribution();
	}
}
