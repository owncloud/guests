<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
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

namespace OCA\Guests;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Template;


class Hooks {

	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * @var IUserSession
	 */
	private $userSession;
	/**
	 * @var IRequest
	 */
	private $request;

	/**
	 * @var Mail
	 */
	private $mail;

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var GuestsHandler
	 */
	private $handler;

	/**
	 * @var AppWhitelist
	 */
	private $appWhitelist;

	/**
	 * Hooks constructor.
	 *
	 * @param ILogger $logger
	 * @param IUserSession $userSession
	 * @param IRequest $request
	 * @param Mail $mail
	 * @param AppWhitelist $appWhitelist
	 * @param GuestsHandler $handler
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 */
	public function __construct(
		ILogger $logger,
		IUserSession $userSession,
		IRequest $request,
		Mail $mail,
		AppWhitelist $appWhitelist,
		GuestsHandler $handler,
		IUserManager $userManager,
		IConfig $config
	) {
		$this->request = $request;
		$this->logger = $logger;
		$this->userSession = $userSession;
		$this->request = $request;
		$this->mail = $mail;
		$this->appWhitelist = $appWhitelist;
		$this->handler = $handler;
		$this->userManager = $userManager;
		$this->config = $config;
	}

	/**
	 * @var Hooks
	 */
	private static $instance;

	/**
	 * @deprecated use DI
	 * @return Hooks
	 */
	public static function createForStaticLegacyCode() {
		if (!self::$instance) {
			$logger = \OC::$server->getLogger();

			self::$instance = new Hooks(
				$logger,
				\OC::$server->getUserSession(),
				\OC::$server->getRequest(),
				Mail::createForStaticLegacyCode(),
				AppWhitelist::createForStaticLegacyCode(),
				GuestsHandler::createForStaticLegacyCode(),
				\OC::$server->getUserManager(),
				\OC::$server->getConfig()
			);
		}
		return self::$instance;
	}

	/**
	 * generate guest password if new
	 *
	 * @param array $params
	 * @throws \Exception
	 */
	static public function postShareHook($params) {
		$hook = self::createForStaticLegacyCode();
		$hook->handlePostShare(
				$params['shareType'],
				$params['shareWith'],
				$params['itemType'],
				$params['itemSource']
		);
	}

	public function handlePostShare(
		$shareType,
		$shareWith,
		$itemType,
		$itemSource
	) {


		$isGuest = $this->config->getUserValue(
			$shareWith,
			'owncloud',
			'isGuest',
			false
		);

		if (!$isGuest) {
			$this->logger->debug(
				"ignoring user '$shareWith', not a guest",
				['app'=>'guests']
			);

			return;
		}

		if (!($itemType === 'folder' || $itemType === 'file')) {
			$this->logger->debug(
				"ignoring share for itemType '$itemType'",
				['app'=>'guests']
			);

			return;
		}


		$user = $this->userSession->getUser();

		if (!$user) {
			throw new \Exception(
				'post_share hook triggered without user in session'
			);
		}

		$this->logger->debug("checking if '$shareWith' has a password",
			['app'=>'guests']);


		$registerToken = $this->config->getUserValue(
			$shareWith,
			'guests',
			'registerToken',
			null
		);

		$uid = $user->getUID();

		try {
			if ($registerToken) {
				// send invitation
				$this->mail->sendGuestInviteMail(
					$uid,
					$shareWith,
					$itemType,
					$itemSource,
					$registerToken
				);
			}
		} catch (DoesNotExistException $ex) {
			$this->logger->error("'$shareWith' does not exist", ['app'=>'guests']);
		}
	}

	/**
	 * PreSetup static hook which limits access of the user to selected apps
	 *
	 * @param string[] $params
	 */
	static public function preSetup($params) {
		$hook = self::createForStaticLegacyCode();
		$hook->handlePreSetup($params);
	}
	/**
	 * PreSetup static hook which limits access of the user to selected apps
	 *
	 * @param string[] $params
	 */
	public function handlePreSetup($params) {
		$uid = $params['user'];

		if (empty($uid)) {
			return;
		}

		if ($this->handler->isGuest($uid) && $this->appWhitelist->isWhitelistEnabled()) {
			$app = $this->getRequestedApp();
			$whitelist = $this->appWhitelist->getWhitelist();

			if ($app && !in_array($app, $whitelist)) {
				header('HTTP/1.0 403 Forbidden');
				$l = \OC::$server->getL10NFactory()->get('guests');
				Template::printErrorPage($l->t(
					'Access to this resource is forbidden for guests.'
				));
				exit;
			}
		}
	}

	/**
	 * Core has \OC::$REQUESTEDAPP but it isn't set until the routes are matched
	 * taken from \OC\Route\Router::match()
	 */
	private function getRequestedApp() {
		$url = $this->request->getRawPathInfo();
		if (substr($url, 0, 6) === '/apps/') {
			// empty string / 'apps' / $app / rest of the route
			list(, , $app,) = explode('/', $url, 4);
			return  \OC_App::cleanAppId($app);
		} else if (substr($url, 0, 6) === '/core/') {
			return 'core';
		} else if (substr($url, 0, 10) === '/settings/') {
			return 'settings';
		} else if (substr($url, 0, 8) === '/avatar/') {
			return 'avatar';
		} else if (substr($url, 0, 10) === '/heartbeat') {
			return 'heartbeat';
		} else if (substr($url, 0, 13) === '/dav/comments') {
			return 'comments';
		}
		return false;
	}
}
