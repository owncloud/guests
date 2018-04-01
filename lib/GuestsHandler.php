<?php
/**
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
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;


/**
 * Handles guest users logic
 *
 * @package OCA\Guests
 */
class GuestsHandler {

	const GUEST_GID = 'guest_app';
	const DEFAULT_DISPLAY_NAME = 'Guests';

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var IMailer
	 */
	private $mailer;

	/**
	 * @var ISecureRandom
	 */
	private $secureRandom;

	/**
	 * @var EventDispatcherInterface
	 */
    private $eventDispatcher;

	public function __construct(
		IConfig $config,
		IUserManager $userManager,
		IMailer $mailer,
		ISecureRandom $secureRandom,
		EventDispatcherInterface $eventDispatcher
	) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->mailer = $mailer;
		$this->secureRandom = $secureRandom;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * @var GuestsHandler
	 */
	private static $instance;

	/**
	 * @deprecated use DI
	 * @return GuestsHandler
	 */
	public static function createForStaticLegacyCode() {
		if (!self::$instance) {
			self::$instance = new GuestsHandler (
				\OC::$server->getConfig(),
				\OC::$server->getUserManager(),
				\OC::$server->getMailer(),
				\OC::$server->getSecureRandom(),
				\OC::$server->getEventDispatcher()
			);

		}
		return self::$instance;
	}

	/**
	 * Check whether the given user is a guest
	 *
	 * @param string $uid
	 * @return bool
	 */
	public function isGuest($uid) {
		return (bool)$this->config->getUserValue($uid, 'owncloud', 'isGuest', false);
	}

	/**
	 * Get guest group gid
	 *
	 * @return string
	 */
	public function getGuestsGID() {
		return self::GUEST_GID;
	}

	/**
	 * Set guest group displayname
	 *
	 * @param string $group
	 */
	public function setGuestsDisplayName($group) {
		$this->config->setAppValue('guests', 'group', $group);
	}

	/**
	 * Get guest group displayname
	 *
	 * @return string
	 */
	public function getGuestsDisplayName() {
		return $this->config->getAppValue('guests', 'group', self::DEFAULT_DISPLAY_NAME);
	}

	/**
	 * Get all guest users
	 *
	 * @return array
	 */
	public function getGuests() {
		return $this->config->getUsersForUserValue(
			'owncloud',
			'isGuest',
			'1'
		);
	}

	/**
	 * Generates token
	 *
	 * @return bool
	 */
	public function createToken() {
		return $this->secureRandom->getMediumStrengthGenerator()->generate(
				21,
				ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
			);
	}

	/**
	 * Initializes guest identified by email $email and username $displayName. To create guest,
	 * token is required for this user
	 *
	 * @param $email
	 * @param $displayName
	 * @return bool
	 *
	 * @throws \Exception
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function createGuest($email, $displayName, $token) {
		$username = strtolower($email);

		if (empty($email) || !$this->mailer->validateMailAddress($email)) {
			return false;
		}

		if ($this->userManager->userExists($username)) {
			return false;
		}

		$users = $this->userManager->getByEmail($email);
		if (!empty($users)) {
			return false;
		}

		$event = new GenericEvent();
		$this->eventDispatcher->dispatch('OCP\User::createPassword', $event);
		if ($event->hasArgument('password')) {
			$password = $event->getArgument('password');
		} else {
			$password = $this->secureRandom->generate(20);
		}

		$user = $this->userManager->createUser(
			$username,
			$password
		);

		$user->setEMailAddress($email);

		if (!empty($displayName)) {
			$user->setDisplayName($displayName);
		}

		$this->config->setUserValue(
			$username,
			'guests',
			'registerToken',
			$token
		);

		$this->config->setUserValue(
			$username,
			'guests',
			'created',
			time()
		);

		$this->config->setUserValue(
			$username,
			'owncloud',
			'isGuest',
			'1'
		);

		return true;
	}

	/**
	 * Validates the guest for the given email address. Token is required for validation.
	 *
	 * @param string $email
	 * @param string $token
	 * @return bool
	 */
	public function validateGuest($email, $token) {
		$userId = strtolower($email);

		if (empty($email) || !$this->mailer->validateMailAddress($email)) {
			return false;
		}

		if (!$this->isGuest($userId)) {
			return false;
		}

		$registerToken = $this->getGuestToken($userId);
		if ((empty($token) || empty($registerToken) || $registerToken !== $token)
		) {
			return false;
		}

		return true;
	}

	/**
	 * Update the guest for given email with the new password. Valid token s required
	 *
	 * @param string $email
	 * @param string $password
	 * @param string $token
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function updateGuest($email, $password, $token) {
		if (!$this->validateGuest($email, $token)) {
			return false;
		}

		$userId = strtolower($email);
		$user = $this->userManager->get($userId);
		$user->setPassword($password);
		$this->config->deleteUserValue($userId, 'guests', 'registerToken');

		return true;
	}

	/**
	 * Get token for the guest
	 *
	 * @param $uid
	 * @return string
	 */
	private function getGuestToken($uid) {
		return $this->config->getUserValue(
			$uid,
			'guests',
			'registerToken'
		);
	}
}
