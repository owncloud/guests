/**
 * @author Felix Heidecke <felix@heidecke.me>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

(function() {
	if (!OCA.Guests) {
		OCA.Guests = {};
	}

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
			};

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
			};

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
	};

	OCA.Guests.GuestShare = GuestShare;

	OCA.Guests.initGuestSharePlugin = function() {
		OC.Plugins.register('OC.Share.ShareDialogView', {
			attach: function (obj) {

				// Override ShareDialogView
				var oldHandler = obj.autocompleteHandler;
				obj.autocompleteHandler = function(search, response) {

					return oldHandler.call(obj, search, function(result, xhrResult) {
						var searchTerm = search.term.trim();
						// before and after the @ sign we match at least one unicode char
						// which is not in the range below or a word character
						// (which is exempt in the unicode range)
						var emailRegex = /^.*([^\u0000-\u007F]|\w).*@([^\u0000-\u007F]|\w)+.*$/;

						// Add potential guests to the suggestions
						if (emailRegex.exec(searchTerm) !== null) {
							// FIXME: will need some new hooks in core to be able to do this in a clean way
							if (!result || !result.length) {
								// no results, need to hack the message and still display something
								$('.shareWithField')
									.removeClass('error')
									.tooltip('hide')
									.autocomplete("option", "autoFocus", true);
								result = [];
							}

							// only allow guest creation entry if there is no exact match (by user id or email, decided by the server)
							var provideGuestEntry = false;

							if (xhrResult
								&& xhrResult.ocs.meta.statuscode === 100
								&& xhrResult.ocs.data.exact.users.length === 0
							) {
								provideGuestEntry = true;
							}

							// compatibility with OC <= 10.0.3 where xhrResult is not available
							// here we always show the entry as we don't know about exact matches,
							// and the backend might block the request if the guest is referring
							// to an existing email address
							if (!xhrResult) {
								var lowerSearchTerm = searchTerm.toLowerCase();
								if (!_.find(result, function(entry) {
									if (entry && entry.value
										&& entry.value.shareType === OC.Share.SHARE_TYPE_USER
										&& entry.value.shareWith.toLowerCase() === lowerSearchTerm) {
										return true;
									}
									return false;
								})) {
									provideGuestEntry = true;
								}
							}

							if (provideGuestEntry) {
								result.push({
									label: t('core', 'Add {unknown} (guest)', {unknown: searchTerm}),
									value: {
										shareType: OC.Share.SHARE_TYPE_GUEST,
										shareWith: searchTerm
									}
								});
							}
							response(result, xhrResult);
						}
						response(result, xhrResult);
					});
				};

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
				};
			}
		});
	};

})(OC, OCA);
