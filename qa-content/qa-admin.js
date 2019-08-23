/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: ilya-content/ilya-admin.js
	Description: Javascript for admin pages to handle Ajax-triggered operations


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

var ilya_recalc_running = 0;

window.onbeforeunload = function(event)
{
	if (ilya_recalc_running > 0) {
		event = event || window.event;
		var message = ilya_warning_recalc;
		event.returnValue = message;
		return message;
	}
};

function ilya_recalc_click(state, elem, value, noteid)
{
	if (elem.ilya_recalc_running) {
		elem.ilya_recalc_stopped = true;

	} else {
		elem.ilya_recalc_running = true;
		elem.ilya_recalc_stopped = false;
		ilya_recalc_running++;

		document.getElementById(noteid).innerHTML = '';
		elem.ilya_original_value = elem.value;
		if (value)
			elem.value = value;

		ilya_recalc_update(elem, state, noteid);
	}

	return false;
}

function ilya_recalc_update(elem, state, noteid)
{
	if (state) {
		var recalcCode = elem.form.elements.code_recalc ? elem.form.elements.code_recalc.value : elem.form.elements.code.value;
		ilya_ajax_post(
			'recalc',
			{state: state, code: recalcCode},
			function(lines) {
				if (lines[0] == '1') {
					if (lines[2])
						document.getElementById(noteid).innerHTML = lines[2];

					if (elem.ilya_recalc_stopped)
						ilya_recalc_cleanup(elem);
					else
						ilya_recalc_update(elem, lines[1], noteid);

				} else if (lines[0] == '0') {
					document.getElementById(noteid).innerHTML = lines[1];
					ilya_recalc_cleanup(elem);

				} else {
					ilya_ajax_error();
					ilya_recalc_cleanup(elem);
				}
			}
		);
	} else {
		ilya_recalc_cleanup(elem);
	}
}

function ilya_recalc_cleanup(elem)
{
	elem.value = elem.ilya_original_value;
	elem.ilya_recalc_running = null;
	ilya_recalc_running--;
}

function ilya_mailing_start(noteid, pauseid)
{
	ilya_ajax_post('mailing', {},
		function(lines) {
			if (lines[0] == '1') {
				document.getElementById(noteid).innerHTML = lines[1];
				window.setTimeout(function() {
					ilya_mailing_start(noteid, pauseid);
				}, 1); // don't recurse

			} else if (lines[0] == '0') {
				document.getElementById(noteid).innerHTML = lines[1];
				document.getElementById(pauseid).style.display = 'none';

			} else {
				ilya_ajax_error();
			}
		}
	);
}

function ilya_admin_click(target)
{
	var p = target.name.split('_');

	var params = {entityid: p[1], action: p[2]};
	params.code = target.form.elements.code.value;

	ilya_ajax_post('click_admin', params,
		function(lines) {
			if (lines[0] == '1')
				ilya_conceal(document.getElementById('p' + p[1]), 'admin');
			else if (lines[0] == '0') {
				alert(lines[1]);
				ilya_hide_waiting(target);
			} else
				ilya_ajax_error();
		}
	);

	ilya_show_waiting_after(target, false);

	return false;
}

function ilya_version_check(uri, version, elem, isCore)
{
	var params = {uri: uri, version: version, isCore: isCore};

	ilya_ajax_post('version', params,
		function(lines) {
			if (lines[0] == '1')
				document.getElementById(elem).innerHTML = lines[1];
		}
	);
}

function ilya_get_enabled_plugins_hashes()
{
	var hashes = [];
	$('[id^=plugin_enabled]:checked').each(
		function(idx, elem) {
			hashes.push(elem.id.replace("plugin_enabled_", ""));
		}
	);

	$('[name=enabled_plugins_hashes]').val(hashes.join(';'));
}
