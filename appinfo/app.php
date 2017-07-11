<?php
/**
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @author Felix Heidecke <felix@heidecke.me>
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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

<<<<<<< HEAD
$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
	'OCA\Files::loadAdditionalScripts',
	function() {
		\OCP\Util::addScript('guests', 'guestshare');
	}
);
=======
use OCP\API;

\OCP\Util::addScript('guests', 'guests.bundle');
>>>>>>> add guestsfilelist

$config = \OC::$server->getConfig();
$groupName = $config->getAppValue('guests', 'group', \OCA\Guests\GroupBackend::DEFAULT_NAME);

$groupBackend = new \OCA\Guests\GroupBackend($groupName);
\OC::$server->getGroupManager()->addBackend($groupBackend);
\OCP\Util::connectHook('OCP\Share', 'post_shared', '\OCA\Guests\Hooks', 'postShareHook');

$user = \OC::$server->getUserSession()->getUser();

if ($user) {
    // if the whitelist is used
	if ($config->getAppValue('guests', 'usewhitelist', 'true') === 'true') {
		\OCP\Util::connectHook('OC_Filesystem', 'preSetup', '\OCA\Guests\AppWhitelist', 'preSetup');
		// apply whitelist to navigation if guest user
		if ($groupBackend->inGroup($user->getUID(), $groupName)) {
			\OCP\Util::addScript('guests', 'navigation');
		}
	}

	// hide email change field via css for learned guests
	if ($user->getBackendClassName() === 'Guests') {
		\OCP\Util::addStyle('guests', 'personal');
	}
<<<<<<< HEAD
=======

	$eventDispatcher = \OC::$server->getEventDispatcher();
	$eventDispatcher->addListener('OCA\Files::loadAdditionalScripts', function() {
		style('guests', 'guests');
	});

	\OCA\Files\App::getNavigationManager()->add(function () {
		$l = \OC::$server->getL10N('guests');
		return [
				'id' => 'sharingguests',
				'appname' => 'guests',
				'script' => 'list.php',
				'order' => 17,
				'name' => $l->t('Shared with Guests'),
		];
	});

	$OCS = new \OCA\Guests\Controller\GuestShareController(
		\OC::$server->getShareManager(),
		\OC::$server->getGroupManager(),
		\OC::$server->getUserManager(),
		\OC::$server->getRequest(),
		\OC::$server->getRootFolder(),
		\OC::$server->getURLGenerator(),
		\OC::$server->getUserSession()->getUser(),
		\OC::$server->getL10N('files_sharing'),
		\OC::$server->getConfig()
	);

	API::register('get',
		'/apps/guests/api/v1/shares',
		[$OCS, 'getShares'],
		'guests'
	);
>>>>>>> add guestsfilelist
}
