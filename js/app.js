/*
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 * @copyright (C) 2014-2017 ownCloud, Inc.
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

if (!OCA.Guests) {
	/**
	 * @namespace OCA.Guests
	 */
	OCA.Guests = {};
}
/**
 * @namespace
 */
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
				fileActions: this._createFileActions(),
				config: OCA.Files.App.getFilesConfig()
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

$(document).ready(function() {
	$('#app-content-sharingguests').on('show', function(e) {
		OCA.Guests.App.initSharingGuests($(e.target));
	});
	$('#app-content-sharingguests').on('hide', function() {
		OCA.Guests.App.removeSharingGuests();
	});
});

