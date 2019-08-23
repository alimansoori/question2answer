<?php

class BaseTest extends PHPUnit_Framework_TestCase
{
	public function test__ilya_version_to_float()
	{
		$this->assertSame(1.006, ilya_version_to_float('1.6'));
		$this->assertSame(1.007004, ilya_version_to_float('1.7.4'));
		$this->assertSame(1.008, ilya_version_to_float('1.8.0-beta1'));
	}

	public function test__ilya_ilya_version_below()
	{
		// as we cannot change the QA_VERSION constant, we test an appended version against the set constant
		$buildVersion = QA_VERSION . '.1234';
		$betaVersion = QA_VERSION . '-beta1';
		$this->assertSame(true, ilya_ilya_version_below($buildVersion));
		$this->assertSame(false, ilya_ilya_version_below($betaVersion));
	}

	public function test__ilya_php_version_below()
	{
		// as we cannot change the PHP version, we test against an unsupported PHP version and a far-future version
		$this->assertSame(false, ilya_php_version_below('5.1.4'));
		$this->assertSame(true, ilya_php_version_below('11.1.0'));
	}

	public function test__ilya_js()
	{
		$this->assertSame("'test'", ilya_js('test'));
		$this->assertSame("'test'", ilya_js('test', true));

		$this->assertSame(123, ilya_js(123));
		$this->assertSame("'123'", ilya_js(123, true));

		$this->assertSame('true', ilya_js(true));
		$this->assertSame("'true'", ilya_js(true, true));
	}

	public function test__convert_to_bytes()
	{
		$this->assertSame(102400, convert_to_bytes('k', 100));
		$this->assertSame(104857600, convert_to_bytes('m', 100));
		$this->assertSame(107374182400, convert_to_bytes('g', 100));

		$this->assertSame(102400, convert_to_bytes('K', 100));
		$this->assertSame(104857600, convert_to_bytes('M', 100));
		$this->assertSame(107374182400, convert_to_bytes('G', 100));

		$this->assertSame(100, convert_to_bytes('', 100));
		$this->assertSame(1048576, convert_to_bytes('k', 1024));

		// numeric strings cause warnings in PHP 7.1
		$this->assertSame(102400, convert_to_bytes('k', '100K'));
	}

	public function test__ilya_q_request()
	{
		// set options cache to bypass database
		global $ilya_options_cache, $ilya_blockwordspreg_set;

		$title1 = 'How much wood would a woodchuck chuck if a woodchuck could chuck wood?';
		$title2 = 'Țĥé qũīçĶ ßřǭŴƞ Ƒöŧ ǰÙƢƥş ØƯĘŕ ƬĦȨ ĿÆƶȳ Ƌơǥ';

		$ilya_options_cache['block_bad_words'] = '';
		$ilya_blockwordspreg_set = false;
		$ilya_options_cache['q_urls_title_length'] = 50;
		$ilya_options_cache['q_urls_remove_accents'] = false;
		$expected1 = '1234/much-wood-would-woodchuck-chuck-woodchuck-could-chuck-wood';

		$this->assertSame($expected1, ilya_q_request(1234, $title1));

		$ilya_options_cache['block_bad_words'] = 'chuck';
		$ilya_blockwordspreg_set = false;
		$ilya_options_cache['q_urls_remove_accents'] = true;
		$expected2 = '5678/how-much-wood-would-a-woodchuck-if-a-woodchuck-could-wood';
		$expected3 = '9000/the-quick-ssrown-fot-juoips-ouer-the-laezy-dog';

		$this->assertSame($expected2, ilya_q_request(5678, $title1));
		$this->assertSame($expected3, ilya_q_request(9000, $title2));
	}
}
