<?php
require_once ILYA__INCLUDE_DIR.'app/format.php';
require_once ILYA__INCLUDE_DIR.'app/options.php';

class AppFormatTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test basic number formatting (no compact numbers)
	 */
	public function test__ilya_format_number()
	{
		// set options/lang cache to bypass database
		global $ilya_options_cache, $ilya_phrases_full;
		$ilya_options_cache['show_compact_numbers'] = '0';

		$ilya_phrases_full['main']['_decimal_point'] = '.';
		$ilya_phrases_full['main']['_thousands_separator'] = ',';

		$this->assertSame('5.5', ilya_format_number(5.452, 1));
		$this->assertSame('5', ilya_format_number(5.452, 0));
		$this->assertSame('5', ilya_format_number(4.5, 0));
		$this->assertSame('9,123', ilya_format_number(9123, 0));
		$this->assertSame('9,123.0', ilya_format_number(9123, 1));

		// not shortened unless 'show_compact_numbers' is true
		$this->assertSame('5.0', ilya_format_number(5, 1, true));
		$this->assertSame('5.5', ilya_format_number(5.452, 1, true));
		$this->assertSame('5', ilya_format_number(5.452, 0, true));
		$this->assertSame('9,123', ilya_format_number(9123, 0, true));
		$this->assertSame('123,456,789', ilya_format_number(123456789, 0, true));

		// change separators
		$ilya_phrases_full['main']['_decimal_point'] = ',';
		$ilya_phrases_full['main']['_thousands_separator'] = '.';

		$this->assertSame('5,5', ilya_format_number(5.452, 1));
		$this->assertSame('5', ilya_format_number(5.452, 0));
		$this->assertSame('9.123', ilya_format_number(9123, 0));
		$this->assertSame('9.123,0', ilya_format_number(9123, 1));
	}

	/**
	 * Test number formatting including compact numbers (e.g. 1.3k)
	 */
	public function test__ilya_format_number__compact()
	{
		// set options/lang cache to bypass database
		global $ilya_options_cache, $ilya_phrases_full;
		$ilya_options_cache['show_compact_numbers'] = '1';

		$ilya_phrases_full['main']['_decimal_point'] = '.';
		$ilya_phrases_full['main']['_thousands_separator'] = ',';
		$ilya_phrases_full['main']['_thousands_suffix'] = 'k';
		$ilya_phrases_full['main']['_millions_suffix'] = 'm';

		// $decimal parameter ignored when 'show_compact_numbers' is true
		$this->assertSame('5.5', ilya_format_number(5.452, 0, true));
		$this->assertSame('5.5', ilya_format_number(5.452, 1, true));
		$this->assertSame('5', ilya_format_number(5, 1, true));

		$this->assertSame('9.1k', ilya_format_number(9123, 0, true));
		$this->assertSame('9.1k', ilya_format_number(9123, 1, true));
		$this->assertSame('9k', ilya_format_number(9040, 0, true));
		$this->assertSame('9k', ilya_format_number(9040, 1, true));
		$this->assertSame('9.1k', ilya_format_number(9050, 0, true));

		$this->assertSame('123m', ilya_format_number(123456789, 0, true));
		$this->assertSame('23.5m', ilya_format_number(23456789, 1, true));
		$this->assertSame('123m', ilya_format_number(123456789, 1, true));
		$this->assertSame('235m', ilya_format_number(234567891, 1, true));
		$this->assertSame('1,223m', ilya_format_number(1223456789, 0, true));

		$this->assertSame('9,000', ilya_format_number(9000, 0, false));
		$this->assertSame('912.3', ilya_format_number(912.3, 1, false));
		$this->assertSame('123,456,789', ilya_format_number(123456789, 0, false));

		// change separators and compact suffixes
		$ilya_phrases_full['main']['_decimal_point'] = ',';
		$ilya_phrases_full['main']['_thousands_separator'] = '.';
		$ilya_phrases_full['main']['_thousands_suffix'] = 'th';
		$ilya_phrases_full['main']['_millions_suffix'] = 'mi';

		$this->assertSame('9,1th', ilya_format_number(9123, 0, true));
		$this->assertSame('123mi', ilya_format_number(123456789, 0, true));
	}
}
