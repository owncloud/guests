/*
 * Copyright (c) 2018 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

describe('OCA.Guests.FileList tests', function() {
	var testFiles,
		alertStub,
		notificationStub,
		fileList,
		getFilesConfigStub;

	beforeEach(function() {
		getFilesConfigStub = sinon.stub(OCA.Files.App, 'getFilesConfig').returns(new OC.Backbone.Model());
		alertStub = sinon.stub(OC.dialogs, 'alert');
		notificationStub = sinon.stub(OC.Notification, 'show');

		// init parameters and test table elements
		$('#testArea').append(
			'<div id="app-content-container">' +
			// init horrible parameters
			'<input type="hidden" id="dir" value="/"></input>' +
			'<input type="hidden" id="permissions" value="31"></input>' +
			// dummy controls
			'<div id="controls">' +
			'   <div class="actions creatable"></div>' +
			'   <div class="notCreatable"></div>' +
			'</div>' +
			// dummy table
			// TODO: at some point this will be rendered by the fileList class itself!
			'<table id="filestable">' +
			'<thead><tr>' +
			'<th id="headerName" class="hidden column-name">' +
			'<input type="checkbox" id="select_all_files" class="select-all">' +
			'<a class="name columntitle" data-sort="name"><span>Name</span><span class="sort-indicator"></span></a>' +
			'<span class="selectedActions hidden"></span>' +
			'</th>' +
			'<th class="hidden column-mtime">' +
			'<a class="columntitle" data-sort="mtime"><span class="sort-indicator"></span></a>' +
			'</th>' +
			'<th class="column-expiration">' +
			'<a class="columntitle"><span>Expiration date</span></a>' +
			'</th>' +
			'</tr></thead>' +
			'<tbody id="fileList"></tbody>' +
			'<tfoot></tfoot>' +
			'</table>' +
			'<div id="emptycontent">Empty content message</div>' +
			'</div>'
		);

		OC.Plugins.register('OCA.Files.FileList', OCA.Files.TagsPlugin);
	});
	afterEach(function() {
		testFiles = undefined;
		fileList.destroy();
		fileList = undefined;

		notificationStub.restore();
		alertStub.restore();
		getFilesConfigStub.restore();
	});

	describe('loading file list for outgoing guest shares', function() {
		var ocsResponse;

		beforeEach(function() {
			fileList = new OCA.Guests.FileList(
				$('#app-content-container')
			);

			fileList.reload();

			/* jshint camelcase: false */
			ocsResponse = {
				ocs: {
					meta: {
						status: 'ok',
						statuscode: 100,
						message: null
					},
					data: [{
						id: 7,
						item_type: 'file',
						item_source: 49,
						file_source: 49,
						path: '/local path/local name.txt',
						permissions: 27,
						stime: 11111,
						share_type: OC.Share.SHARE_TYPE_USER,
						share_with: 'user2',
						share_with_displayname: 'User Two',
						tags: [OC.TAG_FAVORITE],
						mimetype: 'text/plain',
						uid_owner: 'user1',
						displayname_owner: 'User One'
					}]
				}
			};
		});
		it('render file shares', function() {
			var request;

			expect(fakeServer.requests.length).toEqual(1);
			request = fakeServer.requests[0];
			expect(request.url).toEqual(
				OC.linkToOCS('apps/guests/api/v1') +
				'shares?format=json&include_tags=true'
			);

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify(ocsResponse)
			);

			var $rows = fileList.$el.find('tbody tr');
			var $tr = $rows.eq(0);
			expect($rows.length).toEqual(1);
			expect($tr.attr('data-id')).toEqual('49');
			expect($tr.attr('data-type')).toEqual('file');
			expect($tr.attr('data-file')).toEqual('local name.txt');
			expect($tr.attr('data-path')).toEqual('/local path');
			expect($tr.attr('data-size')).not.toBeDefined();
			expect($tr.attr('data-permissions')).toEqual('31'); // read and delete
			expect($tr.attr('data-mime')).toEqual('text/plain');
			expect($tr.attr('data-mtime')).toEqual('11111000');
			expect($tr.attr('data-share-owner')).not.toBeDefined();
			expect($tr.attr('data-share-id')).toEqual('7');
			expect($tr.attr('data-favorite')).toEqual('true');
			expect($tr.attr('data-tags')).toEqual(OC.TAG_FAVORITE);
			expect($tr.find('a.name').attr('href')).toEqual(
				OC.webroot +
				'/remote.php/webdav/local%20path/local%20name.txt'
			);
			expect($tr.find('.nametext').text().trim()).toEqual('local name.txt');
		});
		it('render folder shares', function() {
			var request;
			/* jshint camelcase: false */
			ocsResponse.ocs.data[0] = _.extend(ocsResponse.ocs.data[0], {
				item_type: 'folder',
				path: '/local path/local name',
			});

			expect(fakeServer.requests.length).toEqual(1);
			request = fakeServer.requests[0];
			expect(request.url).toEqual(
				OC.linkToOCS('apps/guests/api/v1') +
				'shares?format=json&include_tags=true'
			);

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify(ocsResponse)
			);

			var $rows = fileList.$el.find('tbody tr');
			var $tr = $rows.eq(0);
			expect($rows.length).toEqual(1);
			expect($tr.attr('data-id')).toEqual('49');
			expect($tr.attr('data-type')).toEqual('dir');
			expect($tr.attr('data-file')).toEqual('local name');
			expect($tr.attr('data-path')).toEqual('/local path');
			expect($tr.attr('data-size')).not.toBeDefined();
			expect($tr.attr('data-permissions')).toEqual('31'); // read and delete
			expect($tr.attr('data-mime')).toEqual('httpd/unix-directory');
			expect($tr.attr('data-mtime')).toEqual('11111000');
			expect($tr.attr('data-share-owner')).not.toBeDefined();
			expect($tr.attr('data-share-id')).toEqual('7');
			expect($tr.attr('data-favorite')).toEqual('true');
			expect($tr.attr('data-tags')).toEqual(OC.TAG_FAVORITE);
			expect($tr.find('a.name').attr('href')).toEqual(
				OC.webroot +
				'/index.php/apps/files' +
				'?dir=/local%20path/local%20name'
			);
			expect($tr.find('.nametext').text().trim()).toEqual('local name');
		});
	});
});
