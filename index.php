<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	File: index.php
	Description: A stub that only sets up the ILYA root and includes ilya-index.php


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: https://projekt.ir/license.php
*/

// Set base path here so this works with symbolic links for multiple installations

function dump($val = null)
{
    if (!$val)
    {
        die('');
    }

    if (is_array($val))
    {
        die(print_r($val));
    }

    die(print_r($val));
}

define('ILYA_BASE_DIR', dirname(empty($_SERVER['SCRIPT_FILENAME']) ? __FILE__ : $_SERVER['SCRIPT_FILENAME']) . '/');

require 'ilya-include/ilya-index.php';
