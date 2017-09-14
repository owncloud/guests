/**
 * @author Felix Heidecke <felix@heidecke.me>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license GPL-2.0
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
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

var GuestShare = {
	model: null,
	email: null,
	
	addGuest: function (model, email) {
		this.model = model;
		this.email = email;
		
		var self = this;
		var xhrObject = {
			type: 'PUT',
			url: OC.generateUrl('/apps/guests/users'),
			dataType: 'text',
			data: {
				displayName: this.email,
				username: this.email,
				email: this.email
			}
		}

		$.ajax(xhrObject).done(function (xhr) {
			self._addGuestShare();
		}).fail(function (xhr) {
			var response = JSON.parse(xhr.responseText);
			var error = response.errorMessages;
			OC.dialogs.alert(
				error.email, // text
				t('core', 'Error') // title
			);
		});
	},

	_addGuestShare: function () {
		var self = this;
		var attributes = {
			shareType: 0,
			shareWith: this.email,
			permissions: OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_READ | OC.PERMISSION_DELETE,
			path: this.model.fileInfoModel.getFullPath()
		}

		return $.ajax({
			type: 'POST',
			url: OC.linkToOCS('apps/files_sharing/api/v1', 2) + 'shares?format=json',
			data: attributes,
			dataType: 'json'
		}).done(function () {
			if (self.model) {
				self.model.fetch();
			}
		}).fail(function () {
			OCdialogs.alert(
				t('core', 'Error while sharing'), // text
				t('core', 'Error') // title
			);
		});
	},
}

OC.Plugins.register('OC.Share.ShareDialogView', {
	attach: function (obj) {

		// Override ShareDialogView
		var oldHandler = obj.autocompleteHandler;
		obj.autocompleteHandler = function(search, response) {

		    return oldHandler.call(obj, search, function(result) {

				var searchTerm = search.term.trim();

				// Add potential guests to the suggestions
				if (searchTerm.search("@") !== -1) {
					result.push({
						label: t('core', 'Add {unknown} (guest)', {unknown: searchTerm}),
						value: {
							shareType: 4,
							shareWith: searchTerm
						}
					});
				}

				response(result);
		    });
		}
		
		obj._onSelectRecipient = function (e, s) {
			e.preventDefault();

			var $this = $(e.target),
				$loading = obj.$el.find('.shareWithLoading');

			$this.attr('disabled', true).val(s.item.label);
			$loading.removeClass('hidden').addClass('inlineblock');

			if (s.item.value.shareType === OC.Share.SHARE_TYPE_GUEST) {
				if (!GuestShare.addGuest(obj.model, s.item.value.shareWith)) {
					$this.val('').attr('disabled', false);
					$loading.addClass('hidden').removeClass('inlineblock');
				}
			} else {
				obj.model.addShare(s.item.value, {
					success: function () {
						$this.val('').attr('disabled', false);
						$loading.addClass('hidden').removeClass('inlineblock');
					}, error: function (obj, msg) {
						OC.Notification.showTemporary(msg);
						$this.attr('disabled', false).autocomplete('search', $this.val());
						$loading.addClass('hidden').removeClass('inlineblock');
					}
				});
			}
		}
	}
});
