/**
 * ownCloud
 *
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright (C) 2015-2017 ownCloud, Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */
(function() {

	$(document).ready(function () {

		// variables
		var $section = $('#guests');
		var $guestsByGroup = $section.find('#guestsByGroup');
		var $guestGroup = $section.find('#guestGroup');
		var $guestUseWhitelist = $section.find('#guestUseWhitelist');
		var $guestWhitelist = $section.find('#guestWhitelist');
		var $resetWhitelist = $section.find('#guestResetWhitelist');
		var $msg = $section.find('.msg');

		// functions

		var loadConfig = function () {
			OC.msg.startAction($msg, t('guests', 'Loading…'));
			$.get(
				OC.generateUrl('apps/guests/config'),
				'',
				function (data) {
					// update model
					config = data;
					// update ui
					if (config.useWhitelist) {
						$guestUseWhitelist.prop('checked', true);
						$guestWhitelist.show();
						$resetWhitelist.show();
					} else {
						$guestUseWhitelist.prop('checked', false);
						$guestWhitelist.hide();
						$resetWhitelist.hide();
					}
					if (config.group) {
						$guestGroup.val(config.group);
					} else {
						$guestGroup.val('');
					}
					if ($.isArray(config.whitelist)) {
						$guestWhitelist.val(config.whitelist.join());
					} else {
						$guestWhitelist.val('');
					}
				},
				'json'
			).then(function() {
					var data = { status: 'success',	data: {message: t('guests', 'Loaded')} };
					OC.msg.finishedAction($msg, data);
				}, function(result) {
					var data = { status: 'error', data:{message:result.responseJSON.message} };
					OC.msg.finishedAction($msg, data);
				});
		};

		var saveConfig = function () {
			OC.msg.startSaving($msg);
			$.ajax({
				type: 'PUT',
				url: OC.generateUrl('apps/guests/config'),
				data: config,
				dataType: 'json'
			}).success(function(data) {
				OC.msg.finishedSaving($msg, data);
			}).fail(function(result) {
				var data = { status: 'error', data:{message:result.responseJSON.message} };
				OC.msg.finishedSaving($msg, data);
			});
		};

		// load initial config
		loadConfig();

		var updateConditions = function () {
			var conditions = [];

			if ($guestsByGroup.prop('checked')) {
				conditions.push('group');
			}
			config.conditions = conditions;
		};
		
		var saveGroup = function () {
			config.group = $guestGroup.val();
			saveConfig();			
		}
		
		var saveWhitelist = function () {
			var apps = $guestWhitelist.val().split(',');
			config.whitelist = [];
			$.each(apps, function( index, value ) {
				config.whitelist.push(value.trim());
			});
			saveConfig();			
		}

		// listen to ui changes
		$guestsByGroup.on('change', function () {
			updateConditions();
			saveConfig();
		});

		$guestGroup.on('change', function () {
			saveGroup();
		});
		
		$guestGroup.keypress(function (e) {
			var key = e.which;
			if (key == 13) {
				saveGroup();
				return true;
			}
		});
		
		$guestUseWhitelist.on('change', function () {
			config.useWhitelist = $guestUseWhitelist.prop('checked');
			if(config.useWhitelist) {
				$guestWhitelist.show();
				$resetWhitelist.show()
			} else {
				$guestWhitelist.hide();
				$resetWhitelist.hide();
			}
			saveConfig();
		});
		
		$guestWhitelist.on('change', function () {
			saveWhitelist();
		});
		
		$guestWhitelist.keypress(function (e) {
			var key = e.which;
			if (key == 13) {
				saveWhitelist();
				return true;
			}
		});
		
		$resetWhitelist.on('click', function () {
			OC.msg.startSaving($msg);
			$.ajax({
				type: 'POST',
				url: OC.generateUrl('apps/guests/whitelist/reset')
			}).success(function(response) {
				config.whitelist = response.whitelist;
				//update ui
				if ($.isArray(config.whitelist)) {
					$guestWhitelist.val(config.whitelist.join());
				} else {
					$guestWhitelist.val('');
				}
				OC.msg.finishedSaving($msg, {
					status:'success',
					data: { message:t('guests', 'Reset') }
				});
			}).fail(function(response) {
				OC.msg.finishedSaving($msg, {
					status: 'error',
					data: { message: response.responseJSON.message }
				});
			});
		});
		
	});

})();
