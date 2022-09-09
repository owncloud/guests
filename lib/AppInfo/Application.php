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

use OCA\Guests\AppWhitelist;
use OCA\Guests\Capabilities;
use OCA\Guests\Hooks;
use OCA\Guests\Mail;
use OCP\AppFramework\App;
use OCP\GroupInterface;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\User\UserExtendedAttributesEvent;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @package OCA\Guests\AppInfo
 */
class Application extends App {
	public const APP_NAME = 'guests';

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
		$container->registerService('Capabilities', function () {
			return new Capabilities();
		});
		$container->registerCapability('Capabilities');

		$server->getEventDispatcher()->addListener(
			UserExtendedAttributesEvent::USER_EXTENDED_ATTRIBUTES,
			function (UserExtendedAttributesEvent $attributesEvent) use ($server) {
				$userAppAttributes = $attributesEvent->getAttributes();
				$this->addUserAppAttributes($server, $attributesEvent, $userAppAttributes);
			}
		);
	}

	/**
	 * Sets the app attributes for the user.
	 * The guestUser, whitelistedApps are added only if they don't exist in the
	 * attributes array. Also, the attributes are updated only if the previous value
	 * does not match with the current attribute value.
	 *
	 * @param IServerContainer $server
	 * @param UserExtendedAttributesEvent $attributesEvent
	 * @param $userAppAttributes
	 */
	private function addUserAppAttributes(IServerContainer $server, UserExtendedAttributesEvent $attributesEvent, $userAppAttributes) {
		$user = $attributesEvent->getUser();
		$isGuestUser = $server->getConfig()->getUserValue($user->getUID(), 'owncloud', 'isGuest', '0');
		if ($isGuestUser === '0') {
			return;
		}
		$appWhiteList = AppWhitelist::getWhitelist();

		/**
		 * Add whitelistedAppsForGuests attribute only if it is not present. There is a
		 * case where the whitelistedAppsForGuests could be changed by the admin.
		 * Under such circumstance, if the whiteListedApp is present in
		 * userAppAttributes array we have to update it.
		 */
		if (isset($userAppAttributes['whitelistedAppsForGuests']) &&
			$userAppAttributes['whitelistedAppsForGuests'] === $appWhiteList) {
			return;
		}

		if ($attributesEvent->setAttributes('whitelistedAppsForGuests', $appWhiteList)) {
			$server->getLogger()->debug(
				"Add new user attributes key 'whitelistedAppsForGuests' has value '" . \implode(',', $appWhiteList) . "' for guest user " . $user->getUID()
			);
		} else {
			$server->getLogger()->debug(
				"The attributes key 'whitelistedAppsForGuests' already exists for guest user " . $user->getUID()
			);
		}
	}

	/**
	 * @return GroupInterface
	 */
	public function registerBackend() {
		$container = $this->getContainer();
		$server = $container->getServer();
		$groupName = $this->getGroupName();
		$groupBackend = new \OCA\Guests\GroupBackend($container->query(IConfig::class), $groupName);
		$server->getGroupManager()->addBackend($groupBackend);
		return $groupBackend;
	}

	/**
	 * Setup events
	 *
	 * @param GroupInterface $groupBackend
	 */
	public function registerListeners(GroupInterface $groupBackend) {
		$container = $this->getContainer();
		$server = $container->getServer();
		$user = $server->getUserSession()->getUser();
		if ($user === null) {
			$this->registerPostShareHook();
			return;
		}

		$groupName = $this->getGroupName();
		$isGuest = $groupBackend->inGroup($user->getUID(), $groupName);

		// if the whitelist is used
		if ($server->getConfig()->getAppValue(self::APP_NAME, 'usewhitelist', 'true') === 'true') {
			\OCP\Util::connectHook('OC_Filesystem', 'preSetup', '\OCA\Guests\AppWhitelist', 'preSetup');
			// apply whitelist to navigation if guest user
			if ($isGuest) {
				\OCP\Util::addScript(self::APP_NAME, 'navigation');
			}
		}

		// hide email change field via css for learned guests
		if ($isGuest) {
			\OCP\Util::addStyle(self::APP_NAME, 'personal');
		} else {
			$eventDispatcher = $server->getEventDispatcher();
			$eventDispatcher->addListener(
				'OCA\Files::loadAdditionalScripts',
				function () {
					\OCP\Util::addScript(self::APP_NAME, 'guestshare');
				}
			);
			$this->registerPostShareHook();
		}
	}

	/**
	 * @return void
	 */
	protected function registerPostShareHook() {
		$container = $this->getContainer();
		$server = $container->getServer();
		$eventDispatcher = $server->getEventDispatcher();
		$eventDispatcher->addListener(
			'share.afterCreate',
			function (GenericEvent $event) use ($container) {
				/** @var Hooks $hooks */
				$hooks = $container->query('Hooks');
				$hooks->handlePostShare($event->getArgument('shareObject'));
			}
		);
	}

	/**
	 * @return string
	 */
	protected function getGroupName() {
		$config = $this->getContainer()->getServer()->getConfig();
		return $config->getAppValue(
			self::APP_NAME,
			'group',
			\OCA\Guests\GroupBackend::DEFAULT_NAME
		);
	}
}
