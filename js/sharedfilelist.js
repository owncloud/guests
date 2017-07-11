/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function() {

	/**
	 * @class OCA.Sharing.FileList
	 * @augments OCA.Files.FileList
	 *
	 * @classdesc Sharing file list.
	 * Contains both "shared with others" and "shared with you" modes.
	 *
	 * @param $el container element with existing markup for the #controls
	 * and a table
	 * @param [options] map of options, see other parameters
	 */
	var FileList = function($el, options) {
		this.initialize($el, options);
	};
	FileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
		/** @lends OCA.Sharing.FileList.prototype */ {
		appName: 'Guests',

		_clientSideSort: true,
		_allowSelection: false,

		/**
		 * @private
		 */
		initialize: function($el, options) {
			OCA.Files.FileList.prototype.initialize.apply(this, arguments);
			if (this.initialized) {
				return;
			}
		},

		_renderRow: function() {
			// HACK: needed to call the overridden _renderRow
			// this is because at the time this class is created
			// the overriding hasn't been done yet...
			return OCA.Files.FileList.prototype._renderRow.apply(this, arguments);
		},

		_createRow: function(fileData) {
			// TODO: hook earlier and render the whole row here
			var $tr = OCA.Files.FileList.prototype._createRow.apply(this, arguments);
			$tr.find('.filesize').remove();
			$tr.find('td.date').before($tr.children('td:first'));
			$tr.find('td.filename input:checkbox').remove();
			$tr.attr('data-share-id', _.pluck(fileData.shares, 'id').join(','));

			return $tr;
		},

		updateEmptyContent: function() {
			var dir = this.getCurrentDirectory();
			if (dir === '/') {
				// root has special permissions
				this.$el.find('#emptycontent').toggleClass('hidden', !this.isEmpty);
				this.$el.find('#filestable thead th').toggleClass('hidden', this.isEmpty);
				this.$el.find('th.column-expiration').addClass('hidden');
			}
			else {
				OCA.Files.FileList.prototype.updateEmptyContent.apply(this, arguments);
			}
		},

		getDirectoryPermissions: function() {
			return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
		},

		updateStorageStatistics: function() {
			// no op because it doesn't have
			// storage info like free space / used space
		},

		reload: function() {
			this.showMask();
			if (this._reloadCall) {
				this._reloadCall.abort();
			}

			// there is only root
			this._setCurrentDir('/', false);

			var promises = [];
			var shares = $.ajax({
				url: OC.linkToOCS('apps/guests/api/v1') + 'shares',
				/* jshint camelcase: false */
				data: {
					format: 'json',
					include_tags: true
				},
				type: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('OCS-APIREQUEST', 'true');
				},
			});
			promises.push(shares);

			this._reloadCall = $.when.apply($, promises);
			var callBack = this.reloadCallback.bind(this);
			return this._reloadCall.then(callBack);
		},

		reloadCallback: function(shares) {
			delete this._reloadCall;
			this.hideMask();

			var files = [];

			if (shares.ocs && shares.ocs.data) {
				files = files.concat(this._makeFilesFromShares(shares.ocs.data));
			}

			this.setFiles(files);
			return true;
		},

		/**
		 * Converts the OCS API share response data to a file info
		 * list
		 * @param {Array} data OCS API share array
		 * @return {Array.<OCA.Sharing.SharedFileInfo>} array of shared file info
		 */
		_makeFilesFromShares: function(data) {
			/* jshint camelcase: false */
			var self = this;
			var files = data;

			// OCS API uses non-camelcased names
			files = _.chain(files)
				// convert share data to file data
				.map(function(share) {
					// TODO: use OC.Files.FileInfo
					var file = {
						id: share.file_source,
						icon: OC.MimeType.getIconUrl(share.mimetype),
						mimetype: share.mimetype,
						tags: share.tags || []
					};
					if (share.item_type === 'folder') {
						file.type = 'dir';
						file.mimetype = 'httpd/unix-directory';
					}
					else {
						file.type = 'file';
					}
					file.share = {
						id: share.id,
						type: share.share_type,
						target: share.share_with,
						stime: share.stime * 1000,
						expiration: share.expiration,
					};
					file.name = OC.basename(share.path);
					file.path = OC.dirname(share.path);
					file.permissions = OC.PERMISSION_ALL;
					if (file.path) {
						file.extraData = share.path;
					}
					return file;
				})
				// Group all files and have a "shares" array with
				// the share info for each file.
				//
				// This uses a hash memo to cumulate share information
				// inside the same file object (by file id).
				.reduce(function(memo, file) {
					var data = memo[file.id];
					var recipient = file.share.targetDisplayName;
					if (!data) {
						data = memo[file.id] = file;
						data.shares = [file.share];
						// using a hash to make them unique,
						// this is only a list to be displayed
						data.recipients = {};
						// share types
						data.shareTypes = {};
						// counter is cheaper than calling _.keys().length
						data.recipientsCount = 0;
						data.mtime = file.share.stime;
					}
					else {
						// always take the most recent stime
						if (file.share.stime > data.mtime) {
							data.mtime = file.share.stime;
						}
						data.shares.push(file.share);
					}

					if (recipient) {
						// limit counterparts for output
						if (data.recipientsCount < 4) {
							// only store the first ones, they will be the only ones
							// displayed
							data.recipients[recipient] = true;
						}
						data.recipientsCount++;
					}

					data.shareTypes[file.share.type] = true;

					delete file.share;
					return memo;
				}, {})
				// Retrieve only the values of the returned hash
				.values()
				// Clean up
				.each(function(data) {
					// convert the recipients map to a flat
					// array of sorted names
					data.mountType = 'shared';
					data.recipients = _.keys(data.recipients);
					data.recipientsDisplayName = OCA.Sharing.Util.formatRecipients(
						data.recipients,
						data.recipientsCount
					);
					delete data.recipientsCount;
					data.shareTypes = _.keys(data.shareTypes);
				})
				// Finish the chain by getting the result
				.value();

			// Sort by expected sort comparator
			return files.sort(this._sortComparator);
		}
	});

	/**
	 * Share info attributes.
	 *
	 * @typedef {Object} OCA.Sharing.ShareInfo
	 *
	 * @property {int} id share ID
	 * @property {int} type share type
	 * @property {String} target share target, either user name or group name
	 * @property {int} stime share timestamp in milliseconds
	 * @property {String} [targetDisplayName] display name of the recipient
	 * (only when shared with others)
	 *
	 */

	/**
	 * Shared file info attributes.
	 *
	 * @typedef {OCA.Files.FileInfo} OCA.Sharing.SharedFileInfo
	 *
	 * @property {Array.<OCA.Sharing.ShareInfo>} shares array of shares for
	 * this file
	 * @property {int} mtime most recent share time (if multiple shares)
	 * @property {String} shareOwner name of the share owner
	 * @property {Array.<String>} recipients name of the first 4 recipients
	 * (this is mostly for display purposes)
	 * @property {String} recipientsDisplayName display name
	 */

	OCA.Guests.FileList = FileList;
})();
