<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Definitions relating to favorites and updates in the database tables


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

if (!defined('ILYA__VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


// Character codes for the different types of entity that can be followed (entitytype columns)

define('ILYA__ENTITY_QUESTION', 'Q');
define('ILYA__ENTITY_USER', 'U');
define('ILYA__ENTITY_TAG', 'T');
define('ILYA__ENTITY_CATEGORY', 'C');
define('ILYA__ENTITY_NONE', '-');


// Character codes for the different types of updates on a post (updatetype columns)

define('ILYA__UPDATE_CATEGORY', 'A'); // questions only, category changed
define('ILYA__UPDATE_CLOSED', 'C'); // questions only, closed or reopened
define('ILYA__UPDATE_CONTENT', 'E'); // title or content edited
define('ILYA__UPDATE_PARENT', 'M'); // e.g. comment moved when converting its parent answer to a comment
define('ILYA__UPDATE_SELECTED', 'S'); // answers only, removed if unselected
define('ILYA__UPDATE_TAGS', 'T'); // questions only
define('ILYA__UPDATE_TYPE', 'Y'); // e.g. answer to comment
define('ILYA__UPDATE_VISIBLE', 'H'); // hidden or reshown


// Character codes for types of update that only appear in the streams tables, not on the posts themselves

define('ILYA__UPDATE_FOLLOWS', 'F'); // if a new question was asked related to one of its answers, or for a comment that follows another
define('ILYA__UPDATE_C_FOR_Q', 'U'); // if comment created was on a question of the user whose stream this appears in
define('ILYA__UPDATE_C_FOR_A', 'N'); // if comment created was on an answer of the user whose stream this appears in
