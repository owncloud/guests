/**
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
	/**
	 * @namespace
	 */
	if (!OCA.Guests) {
		OCA.Guests = {};
	}

	OCA.Guests.App = {

		_guestsFileList: null,

		initSharingGuests: function($el) {
			if (this._guestsFileList) {
				return this._guestsFileList;
			}
			this._guestsFileList = new OCA.Guests.FileList(
				$el,
				{
					id: 'shares.guests',
					scrollContainer: $('#app-content'),
					fileActions: this._createFileActions()
				}
			);

			this._extendFileList(this._guestsFileList);
			this._guestsFileList.appName = t('guests', 'Shared with guests');
			this._guestsFileList.$el.find('#emptycontent').html('<div class="icon-share"></div>' +
				'<h2>' + t('guests', 'Nothing shared with guests yet') + '</h2>' +
				'<p>' + t('guests', 'Files and folders you share with guests will show up here') + '</p>');
			return this._guestsFileList;
		},


		removeSharingGuests: function() {
			if (this._guestsFileList) {
				this._guestsFileList.$fileList.empty();
			}
		},

		/**
		 * Destroy the app
		 */
		destroy: function() {
			OCA.Files.fileActions.off('setDefault.app-guests', this._onActionsUpdated);
			OCA.Files.fileActions.off('registerAction.app-guests', this._onActionsUpdated);
			this.removeSharingGuests();
			this._guestsFileList = null;
			delete this._globalActionsInitialized;
		},

		_createFileActions: function() {
			// inherit file actions from the files app
			var fileActions = new OCA.Files.FileActions();
			// note: not merging the legacy actions because legacy apps are not
			// compatible with the sharing overview and need to be adapted first
			fileActions.registerDefaultActions();
			fileActions.merge(OCA.Files.fileActions);

			if (!this._globalActionsInitialized) {
				// in case actions are registered later
				this._onActionsUpdated = _.bind(this._onActionsUpdated, this);
				OCA.Files.fileActions.on('setDefault.app-sharing', this._onActionsUpdated);
				OCA.Files.fileActions.on('registerAction.app-sharing', this._onActionsUpdated);
				this._globalActionsInitialized = true;
			}

			// when the user clicks on a folder, redirect to the corresponding
			// folder in the files app instead of opening it directly
			fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
				OCA.Files.App.setActiveView('files', {silent: true});
				OCA.Files.App.fileList.changeDirectory(OC.joinPaths(context.$file.attr('data-path'), filename), true, true);
			});
			fileActions.setDefault('dir', 'Open');
			return fileActions;
		},

		_onActionsUpdated: function(ev) {
			_.each([this._guestsFileList], function(list) {
				if (!list) {
					return;
				}

				if (ev.action) {
					list.fileActions.registerAction(ev.action);
				} else if (ev.defaultAction) {
					list.fileActions.setDefault(
						ev.defaultAction.mime,
						ev.defaultAction.name
					);
				}
			});
		},

		_extendFileList: function(fileList) {
			// remove size column from summary
			fileList.fileSummary.$el.find('.filesize').remove();
		}
	};

	if (!window.TESTING) {
		$(document).ready(function () {
			// not all are always loaded, depending on what page is displayed,
			// so need to initialize conditionally

			// guests.js
			if (OCA.Guests.initSettingsPage) {
				OCA.Guests.initSettingsPage();
			}
			// guestshare.js
			if (OCA.Guests.initGuestSharePlugin) {
				OCA.Guests.initGuestSharePlugin();
			}
			// navigation.js
			if (OCA.Guests.updateNavigation) {
				OCA.Guests.updateNavigation();
			}

			$('#app-content-sharingguests').on('show', function(e) {
				OCA.Guests.App.initSharingGuests($(e.target));
			});
			$('#app-content-sharingguests').on('hide', function() {
				OCA.Guests.App.removeSharingGuests();
			});
		});
	}

})();

