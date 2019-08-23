<?php
require_once ILYA__INCLUDE_DIR.'util/string.php';

class UtilStringTest extends PHPUnit_Framework_TestCase
{
	private $strBasic = 'So I tied an onion to my belt, which was the style at the time.';
	private $strAccents = 'Țĥé qũīçĶ ßřǭŴƞ Ƒöŧ ǰÙƢƥş ØƯĘŕ ƬĦȨ ĿÆƶȳ Ƌơǥ';
	private $blockWordString = 't*d o*n b*t style';

	public function test__ilya_string_to_words()
	{
		$test1 = ilya_string_to_words($this->strBasic);
		$expected1 = array('so', 'i', 'tied', 'an', 'onion', 'to', 'my', 'belt', 'which', 'was', 'the', 'style', 'at', 'the', 'time');

		$test2 = ilya_string_to_words($this->strBasic, false);
		$expected2 = array('So', 'I', 'tied', 'an', 'onion', 'to', 'my', 'belt', 'which', 'was', 'the', 'style', 'at', 'the', 'time');

		$this->assertEquals($expected1, $test1);
		$this->assertEquals($expected2, $test2);
	}

	public function test__ilya_string_remove_accents()
	{
		$test = ilya_string_remove_accents($this->strAccents);
		$expected = 'The quicK ssroWn Fot jUOIps OUEr THE LAEzy Dog';

		$this->assertEquals($expected, $test);
	}

	public function test__ilya_slugify()
	{

		$title1 = 'How much wood would a woodchuck chuck if a woodchuck could chuck wood?';
		$title2 = 'Țĥé qũīçĶ ßřǭŴƞ Ƒöŧ ǰÙƢƥş ØƯĘŕ ƬĦȨ ĿÆƶȳ Ƌơǥ';

		$expected1 = 'how-much-wood-would-a-woodchuck-chuck-if-a-woodchuck-could-chuck-wood';
		$expected2 = 'much-wood-would-woodchuck-chuck-woodchuck-could-chuck-wood';
		$expected3 = 'țĥé-qũīçķ-ßřǭŵƞ-ƒöŧ-ǰùƣƥş-øưęŕ-ƭħȩ-ŀæƶȳ-ƌơǥ';
		$expected4 = 'the-quick-ssrown-fot-juoips-ouer-the-laezy-dog';

		$this->assertSame($expected1, ilya_slugify($title1));
		$this->assertSame($expected2, ilya_slugify($title1, true, 50));
		$this->assertSame($expected3, ilya_slugify($title2, false));
		$this->assertSame($expected4, ilya_slugify($title2, true));
	}

	public function test__ilya_tags_to_tagstring()
	{
		$test = ilya_tags_to_tagstring( array('Hello', 'World') );
		$expected = 'Hello,World';

		$this->assertEquals($expected, $test);
	}

	public function test__ilya_tagstring_to_tags()
	{
		$test = ilya_tagstring_to_tags('hello,world');
		$expected = array('hello', 'world');

		$this->assertEquals($expected, $test);
	}

	public function test__ilya_shorten_string_line()
	{
		// ilya_shorten_string_line ($string, $length)

		$test = ilya_shorten_string_line($this->strBasic, 30);

		$this->assertStringStartsWith('So I tied', $test);
		$this->assertStringEndsWith('time.', $test);
		$this->assertNotFalse(strpos($test, '...'));
	}

	public function test__ilya_block_words_explode()
	{
		$test = ilya_block_words_explode($this->blockWordString);
		$expected = array('t*d', 'o*n', 'b*t', 'style');

		$this->assertEquals($expected, $test);
	}

	public function test__ilya_block_words_to_preg()
	{
		$test = ilya_block_words_to_preg($this->blockWordString);
		$expected = '(?<= )t[^ ]*d(?= )|(?<= )o[^ ]*n(?= )|(?<= )b[^ ]*t(?= )|(?<= )style(?= )';

		$this->assertEquals($expected, $test);
	}

	public function test__ilya_block_words_match_all()
	{
		$test1 = ilya_block_words_match_all('onion belt', '');

		$wordpreg = ilya_block_words_to_preg($this->blockWordString);
		$test2 = ilya_block_words_match_all('tried an ocean boat', $wordpreg);
		// matches are returned as array of [offset] => [length]
		$expected = array(
			 0 => 5, // tried
			 9 => 5, // ocean
			15 => 4, // boat
		);

		$this->assertEmpty($test1);
		$this->assertEquals($expected, $test2);
	}

	public function test__ilya_block_words_replace()
	{
		$wordpreg = ilya_block_words_to_preg($this->blockWordString);
		$test = ilya_block_words_replace('tired of my ocean boat style', $wordpreg);
		$expected = '***** of my ***** **** *****';

		$this->assertEquals($expected, $test);
	}

	public function test__ilya_random_alphanum()
	{
		$len = 50;
		$test = ilya_random_alphanum($len);

		$this->assertEquals(strlen($test), $len);
	}

	public function test__ilya_email_validate()
	{
		$goodEmails = array(
			'hello@example.com',
			'q.a@question2answer.org',
			'example@newdomain.app'
		);
		$badEmails = array(
			'nobody@nowhere',
			'pokémon@example.com',
			'email @ with spaces',
			'some random string',
		);

		foreach ($goodEmails as $email) {
			$this->assertTrue( ilya_email_validate($email) );
		}
		foreach ($badEmails as $email)
			$this->assertFalse( ilya_email_validate($email) );
	}

	public function test__ilya_strlen()
	{
		$test = ilya_strlen($this->strAccents);

		$this->assertEquals($test, 43);
	}

	public function test__ilya_strtolower()
	{
		$test = ilya_strtolower('hElLo WoRld');

		$this->assertEquals($test, 'hello world');
	}

	public function test__ilya_substr()
	{
		$test = ilya_substr($this->strBasic, 5, 24);

		$this->assertEquals($test, 'tied an onion to my belt');
	}

	public function test__ilya_string_matches_one()
	{
		$matches = array( 'dyed', 'shallot', 'belt', 'fashion' );
		$nonMatches = array( 'dyed', 'shallot', 'buckle', 'fashion' );

		$this->assertTrue( ilya_string_matches_one($this->strBasic, $matches) );
		$this->assertFalse( ilya_string_matches_one($this->strBasic, $nonMatches) );
	}
}
