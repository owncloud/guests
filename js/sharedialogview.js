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

var Guests = {
	model: null,
	email: null,
	
	addGuest: function (model, email) {
		this.model = model;
		this.email = email;
		
		let self = this;
		let xhrObject = {
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
			let response = JSON.parse(xhr.responseText);
			let error = response.errorMessages;
			OCdialogs.alert(
				error.email, // text
				t('core', 'Error') // title
			);
		});
	},

	_addGuestShare: function () {
		let self = this;		
		let attributes = {
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
			if (self.model)
				self.model.fetch();

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

		// Override ShareDigalogView

		obj.autocompleteHandler = function (search, response) {
			var view = obj;
			var $loading = obj.$el.find('.shareWithLoading');
			$loading.removeClass('hidden');
			$loading.addClass('inlineblock');
			$.get(
				OC.linkToOCS('apps/guests/api/v1') + 'sharees',
				{
					format: 'json',
					search: search.term.trim(),
					perPage: 200,
					itemType: view.model.get('itemType')
				},
				function (result) {
					$loading.addClass('hidden');
					$loading.removeClass('inlineblock');
					if (result.ocs.meta.statuscode == 100) {
						var searchTerm = search.term.trim();
						var users = result.ocs.data.exact.users.concat(result.ocs.data.users);
						var groups = result.ocs.data.exact.groups.concat(result.ocs.data.groups);
						var remotes = result.ocs.data.exact.remotes.concat(result.ocs.data.remotes);
						var unknown = [];

						var usersLength;
						var groupsLength;
						var remotesLength;

						var i, j;

						// Add potential guests to the suggestions
						if (searchTerm.search("@") !== -1) {
							groupName = groupName = "(gast)"; 
							unknown = [{
								label: t('core', 'Add {unknown}', {unknown: searchTerm})
											+ ' ' + groupName,
								value: {
									shareType: 4,
									shareWith: searchTerm
								}
							}];
						}

						//Filter out the current user
						usersLength = users.length;
						for (i = 0; i < usersLength; i++) {
							if (users[i].value.shareWith === OC.currentUser) {
								users.splice(i, 1);
								break;
							}
						}

						// Filter out the owner of the share
						if (view.model.hasReshare()) {
							usersLength = users.length;
							for (i = 0; i < usersLength; i++) {
								if (users[i].value.shareWith === view.model.getReshareOwner()) {
									users.splice(i, 1);
									break;
								}
							}
						}

						var shares = view.model.get('shares');
						var sharesLength = shares.length;
						var alreadyShared = false;

						// Now filter out all sharees that are already shared with
						for (i = 0; i < sharesLength; i++) {
							var share = shares[i];

							if (share.share_type === OC.Share.SHARE_TYPE_USER) {
								usersLength = users.length;
								for (j = 0; j < usersLength; j++) {
									if (users[j].value.shareWith === share.share_with) {
										users.splice(j, 1);
										alreadyShared = true;
										break;
									}
								}
							} else if (share.share_type === OC.Share.SHARE_TYPE_GROUP) {
								groupsLength = groups.length;
								for (j = 0; j < groupsLength; j++) {
									if (groups[j].value.shareWith === share.share_with) {
										groups.splice(j, 1);
										break;
									}
								}
							} else if (share.share_type === OC.Share.SHARE_TYPE_REMOTE) {
								remotesLength = remotes.length;
								for (j = 0; j < remotesLength; j++) {
									if (remotes[j].value.shareWith === share.share_with) {
										remotes.splice(j, 1);
										break;
									}
								}
							}
						}

						// Do not offer guest user creation, when email address belongs to an existing user
						var emailExists = false;
						usersLength = users.length;
						for (i = 0; i < usersLength; i++) {
							if (users[i].value.email === searchTerm) {
								emailExists = true;
								break;
							}
						}

						var suggestions = users.concat(groups).concat(remotes);
						if (!(emailExists || alreadyShared)) {
							suggestions = suggestions.concat(unknown);
						}

						if (suggestions.length > 0) {
							$('.shareWithField').removeClass('error')
								.tooltip('hide')
								.autocomplete("option", "autoFocus", true);
							response(suggestions);
						} else {
							if (!alreadyShared) {
								var title = t('core', 'No users or groups found for {search}', {search: $('.shareWithField').val()});
								if (!view.configModel.get('allowGroupSharing')) {
									title = t('core', 'No users found for {search}', {search: $('.shareWithField').val()});
								}
								$('.shareWithField').addClass('error')
									.attr('data-original-title', title)
									.tooltip('hide')
									.tooltip({
										placement: 'bottom',
										trigger: 'manual'
									})
									.tooltip('fixTitle')
									.tooltip('show');
								response();
							}
						}
					} else {
						response();
					}
				}
			).fail(function () {
				$loading.addClass('hidden');
				$loading.removeClass('inlineblock');
				OC.Notification.show(t('core', 'An error occurred. Please try again'));
				window.setTimeout(OC.Notification.hide, 5000);
			});
		};

		obj._onSelectRecipient = function (e, s) {
			e.preventDefault();

			// vars starting with $ are jQuery DOM objects
			// ---
			var $this = $(e.target),
				$loading = obj.$el.find('.shareWithLoading');

			$this.attr('disabled', true).val(s.item.label);
			$loading.removeClass('hidden').addClass('inlineblock');

			// Init OCA.Guests.App if share is of type guest
			// ---
			if (s.item.value.shareType === OC.Share.SHARE_TYPE_GUEST) {
				if (!Guests.addGuest(obj.model, s.item.value.shareWith)) {
					$this.val('').attr('disabled', false);
					$loading.addClass('hidden').removeClass('inlineblock');
				}
			}
			else {
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

