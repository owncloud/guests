<?php
/**
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
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

namespace OCA\Guests\AppInfo;

use OCA\Guests\Hooks;
use OCA\Guests\Mail;
use OCP\AppFramework\App;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @package OCA\Guests\AppInfo
 */
class Application extends App {
	const APP_NAME = 'guests';

	/**
	 * Application constructor
	 */
	public function __construct() {
		parent::__construct(self::APP_NAME);
		$container = $this->getContainer();
		$server = $container->getServer();

		$container->registerService(
			'Hooks',
			function ($c) use ($server) {
				return new Hooks(
					$server->getLogger(),
					$server->getUserSession(),
					$c->query('Mail'),
					$server->getConfig()
				);
			}
		);
		$container->registerService(
			'Mail',
			function ($c) use ($server) {
				return new Mail(
					$server->getLogger(),
					$server->getUserSession(),
					$server->getMailer(),
					new \OCP\Defaults(),
					$server->getL10N(self::APP_NAME),
					$server->getUserManager(),
					$server->getURLGenerator()
				);
			}
		);
	}

	/**
	 * Setup events
	 */
	public function registerListeners() {
		$container = $this->getContainer();
		$server = $container->getServer();
		$eventDispatcher = $server->getEventDispatcher();

		$eventDispatcher->addListener(
			'OCA\Files::loadAdditionalScripts',
			function () {
				\OCP\Util::addScript(self::APP_NAME, 'guestshare');
			}
		);
		$eventDispatcher->addListener(
			'share.afterCreate',
			function (GenericEvent $event) use ($container) {
				/** @var Hooks $hooks */
				$hooks = $container->query('Hooks');
				$hooks->handlePostShare($event->getArgument('shareObject'));
			}
		);
	}
}
