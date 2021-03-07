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

$app = new \OCA\Guests\AppInfo\Application();
$groupBackend = $app->registerBackend();
$app->registerListeners($groupBackend);

// this will initialize the
\OCP\Util::addScript('guests', 'app');

\OCA\Files\App::getNavigationManager()->add(function () {
	$l = \OC::$server->getL10N('guests');
	return [
		'id' => 'sharingguests',
		'appname' => 'guests',
		'script' => 'list.php',
		'order' => 17,
		'name' => $l->t('Shared with guests'),
	];
});

$OCS = new \OCA\Guests\Controller\GuestShareController(
	\OC::$server->getShareManager(),
	\OC::$server->getGroupManager(),
	\OC::$server->getUserManager(),
	\OC::$server->getRequest(),
	\OC::$server->getRootFolder(),
	\OC::$server->getURLGenerator(),
	\OC::$server->getUserSession(),
	\OC::$server->getL10N('files_sharing'),
	\OC::$server->getConfig()
);

\OCP\API::register('get',
	'/apps/guests/api/v1/shares',
	[$OCS, 'getShares'],
	'guests'
);
