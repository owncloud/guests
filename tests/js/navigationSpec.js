/*
 * Copyright (c) 2018 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

describe('Navigation tests', function() {
	var $navigation;

	beforeEach(function() {
		$navigation = $('<ul id="navigation"></ul>');
		$('#testArea').append($navigation);

		$navigation.append('<li href="#" data-id="files"></li>');
		$navigation.append('<li href="#" data-id="in-list1"></li>');
		$navigation.append('<li href="#" data-id="not-in-list1"></li>');
	});

	afterEach(function() { 
		$navigation.remove(); 
	});

	it('removes non-whitelisted apps from navigation  menu', function() {
		OCA.Guests.updateNavigation();

		expect(fakeServer.requests.length).toEqual(1);
		expect(fakeServer.requests[0].url).toEqual(OC.generateUrl('/apps/guests/whitelist'));
		fakeServer.requests[0].respond(
			200,
			{ 'Content-Type': 'application/json' },
			JSON.stringify({
				enabled: true,
				apps: [
					'files',
					'in-list1'
				]
			})
		);

		var $entries = $navigation.find('li');
		expect($entries.length).toEqual(2);
		expect($entries.eq(0).data('id')).toEqual('files');
		expect($entries.eq(1).data('id')).toEqual('in-list1');
	});
});
