<?php
/**
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

\OCP\Util::addScript('guests', 'guests.bundle');

$config = \OC::$server->getConfig();
$groupName = $config->getAppValue('guests', 'group', 'guest_app');

\OC::$server->getGroupManager()->addBackend(new \OCA\Guests\GroupBackend($groupName));
\OCP\Util::connectHook('OCP\Share', 'post_shared', '\OCA\Guests\Hooks', 'postShareHook');

$user = \OC::$server->getUserSession()->getUser();

if ($user) {
    // if the whitelist is used
	if ($config->getAppValue('guests', 'usewhitelist', 'true') === 'true') {
		\OCP\Util::connectHook('OC_Filesystem', 'preSetup', '\OCA\Guests\AppWhitelist', 'preSetup');
	}

	// hide email change field via css for learned guests
	if ($user->getBackendClassName() === 'Guests') {
		\OCP\Util::addStyle('guests', 'personal');
	}
}
