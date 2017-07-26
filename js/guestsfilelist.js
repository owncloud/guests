/*
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright (C) 2014-2017 ownCloud, Inc.
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function() {

	/**
	 * @class OCA.Guest.FileList
	 * @augments OCA.Sharing.FileList
	 *
	 * @classdesc Sharing file list.
	 * Provides "shared with guests"
	 *
	 * @param $el container element with existing markup for the #controls
	 * and a table
	 * @param [options] map of options, see other parameters
	 */
	var FileList = function($el, options) {
		this.initialize($el, options);
	};
	FileList.prototype = _.extend({}, OCA.Sharing.FileList.prototype,
		/** @lends OCA.Sharing.FileList.prototype */ {
		appName: 'Guests',

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
			return this._reloadCall.then(
				function(shares) {
					// reloadCallback requires list of shares
					callBack([shares]); 
				}
			);
		}
	});

	OCA.Guests.FileList = FileList;
})();
