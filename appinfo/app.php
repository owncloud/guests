<?php
/**
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @author Felix Heidecke <felix@heidecke.me>
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Piotr Mrowczynski <piotr@owncloud.com>
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

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
	'OCA\Files::loadAdditionalScripts',
	function() {
		\OCP\Util::addScript('guests', 'guestshare');
	}
);

$appWhiteList = new \OCA\Guests\AppWhitelist(
	\OC::$server->getConfig()
);
$handler = new \OCA\Guests\GuestsHandler(
	\OC::$server->getConfig(),
	\OC::$server->getUserManager(),
	\OC::$server->getMailer(),
	\OC::$server->getSecureRandom(),
	\OC::$server->getEventDispatcher()
);
$groupBackend = new \OCA\Guests\GroupBackend(
	$handler
);
\OC::$server->getGroupManager()->addBackend($groupBackend);
\OCP\Util::connectHook('OCP\Share', 'post_shared', '\OCA\Guests\Hooks', 'postShareHook');

$user = \OC::$server->getUserSession()->getUser();

if ($user) {
    // if the whitelist is used
	if ($appWhiteList->isWhitelistEnabled()) {
		\OCP\Util::connectHook('OC_Filesystem', 'preSetup', '\OCA\Guests\Hooks', 'preSetup');
		// apply whitelist to navigation if guest user
		$groupName = $handler->getGuestsGID();
		if ($groupBackend->inGroup($user->getUID(), $groupName)) {
			\OCP\Util::addScript('guests', 'navigation');
		}
	}

	// hide email change field via css for learned guests
	if ($user->getBackendClassName() === 'Guests') {
		\OCP\Util::addStyle('guests', 'personal');
	}
}
