<?php
/**
 * ownCloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING-AGPL file.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @copyright Jörn Friedrich Dreyer 2015
 */

namespace OCA\Guests;

use OCA\Guests\Db\GuestMapper;
use OCA\Guests\Files\Storage\Wrapper\DirMask;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Contacts\IManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\IHasher;
use OCP\Share;
use OCP\UserInterface;


class Backend implements UserInterface, IUserBackend {

	/** @var IConfig */
	private $config;

	/** @var ILogger */
	private $logger;

	/** @var GuestMapper */
	private $mapper;

	/** @var IHasher */
	private $hasher;

	/** @var IManager */
	private $contactsManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IUserSession */
	private $userSession;

	/** @var IGroupManager */
	private $groupManager;

	public function __construct(
		IConfig $config,
		ILogger $logger,
		GuestMapper $mapper,
		IHasher $hasher,
		IManager $contactsManager,
		IUserManager $userManager,
		IUserSession $userSession,
		IGroupManager $groupManager
	) {
		$this->config = $config;
		$this->logger = $logger;
		$this->mapper = $mapper;
		$this->hasher = $hasher;
		$this->contactsManager = $contactsManager;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
	}

	/**
	 * @var Backend
	 */
	private static $instance;

	/**
	 * @deprecated use DI
	 * @return Backend
	 */
	public static function createForStaticLegacyCode() {
		if (!self::$instance) {
			$logger = \OC::$server->getLogger();

			self::$instance = new Backend(
				\OC::$server->getConfig(),
				$logger,
				new GuestMapper(\OC::$server->getDatabaseConnection(), $logger),
				\OC::$server->getHasher(),
				\OC::$server->getContactsManager(),
				\OC::$server->getUserManager(),
				\OC::$server->getUserSession(),
				\OC::$server->getGroupManager()
			);
		}
		return self::$instance;
	}

	/**
	* Check if backend implements actions
	* @param array $actions bitwise-or'ed actions
	* @returns boolean
	*
	* Returns the supported actions as int to be
	* compared with OC_USER_BACKEND_CREATE_USER etc.
	*/
	public function implementsActions($actions) {
		return (bool)((\OC_User_Backend::CHECK_PASSWORD
			| \OC_User_Backend::SET_PASSWORD
			| \OC_User_Backend::GET_DISPLAYNAME
			//| \OC_User_Backend::PROVIDE_AVATAR
			//| \OC_User_Backend::COUNT_USERS
			) & $actions);
	}


	/**
	 * returns a counts of the users
	 *
	 * @return int | bool
	 */
	public function countUsers() {
		return false;
	}

	/**
	* delete a user
	* @param string $uid The username of the user to delete
	* @returns boolean
	*
	* Deletes a user
	*/
	public function deleteUser($uid) {
		return false;
	}

	/**
	 * Get a list of all users
	 *
	 * @param string $search
	 * @param integer|null $limit
	 * @param integer|null $offset
	 * @return string[] with all uids
	 */
	public function getUsers($search = '', $limit = null, $offset = null) {
		$this->logger->debug("getUsers '$search'",
			['app'=>'guests']);
		$guests = $this->mapper->search($search, $limit, $offset);
		$results = [];
		if ($guests) {
			foreach ($guests as $guest) {
				$results[] = $guest->getUid();
			}
		} else {
			if (filter_var($search, FILTER_VALIDATE_EMAIL)) {
				$results[] = $search;
			}
		}
		return $results;
	}


	/**
	* check if a user exists
	* @param string $uid the username
	* @return boolean
	*/
	public function userExists($uid) {
		/* TODO fetch guest from contacts app
		$guests = $this->contactsManager->search($uid, ['EMAIL']);
		if ($guests) {
			return true;
		}
		*/
		try {
			$guest = $this->mapper->findByUid($uid);
			return true;
		} catch (DoesNotExistException $ex) {
			return false;
		}
	}

	public function hasValidIncomingShares($uid) {
		$shares = Share::getItemsSharedWithUser('file', $uid);
		foreach ($shares as $share) {
			if (empty($share['expiration'])) {
				return true;
			}
			//TODO check expiration when internal shares can have en expiration
		}
		return false;
	}

	/**
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string display name
	 */
	public function getDisplayName($uid) {
		$l = \OC::$server->getL10N('guests');
		return "$uid ({$l->t('Guest')})";
	}

	/**
	 * Get a list of all display names
	 * @param string $search
	 * @param int|null $limit
	 * @param int|null $offset
	 * @returns array with  all displayNames (value) and the corresponding uids (key)
	 *
	 * Get a list of all display names and user ids.
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		$this->logger->debug("getUsers '$search'", ['app'=>'guests']);
		if (filter_var($search, FILTER_VALIDATE_EMAIL)) {
			$l = \OC::$server->getL10N('guests');
			return [$search => "$search ({$l->t('Guest')})"];
		}
		return [];
	}

	/**
	 * Check if a user list is available or not
	 * @return boolean if users can be listed or not
	 */
	public function hasUserListings() {
		return false;
	}

	/**
	 * Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return string
	 *
	 * Check if the password is correct without logging in the user
	 * returns the user id or false
	 */
	public function checkPassword($uid, $password) {
		try {
			$guest = $this->mapper->findByUid($uid);
			$storedHash = $guest->getHash();
			$newHash = '';
			if ($this->hasher->verify($password, $storedHash, $newHash)) {
				if (!empty($newHash)) {
					$guest->setHash($newHash);
					$this->mapper->update($guest);
				}
				// guests should not receive the skeleton
				$this->createEmptyUserFolder($guest->getUid());

				if ($this->hasValidIncomingShares($uid)) {
					return $guest->getUid();
				}
			}
			return false;
		} catch (DoesNotExistException $ex) {
			return false;
		}
	}

	public function createEmptyUserFolder($uid) {
		$root = \OC::$server->getRootFolder();
		if (!$root->nodeExists($uid)) {
			$folder = $root->newFolder($uid);
		} else {
			$folder = $root->get($uid);
		}

		$dir = '/files';
		if (!$folder->nodeExists($dir)) {
			$folder->newFolder($dir);
		}
	}
	/**
	 * Set password
	 * @param string $uid The username
	 * @param string $password The new password
	 * @return bool
	 *
	 * Change the password of a user
	 */
	public function setPassword($uid, $password) {
		try {
			$guest = $this->mapper->findByUid($uid);
			$guest->setHash($this->hasher->hash($password));
			$this->mapper->update($guest);
			return true;
		} catch (DoesNotExistException $ex) {
			return false;
		}
	}

	/**
	 * Backend name to be shown in user management
	 *
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName() {
		return 'Guests';
	}

	/**
	 * HACK to add contact before sharing code checks if the user exists
	 */
	public function interceptShareRequest() {
		if (isset($_SERVER['PATH_INFO'])
			&& $_SERVER['PATH_INFO'] === '/core/ajax/share.php'
			&& isset($_POST['action']) && $_POST['action'] === 'share'
			&& isset($_POST['shareType']) && $_POST['shareType'] == '0'
			&& isset($_POST['shareWith'])
			&& filter_var($_POST['shareWith'], FILTER_VALIDATE_EMAIL)
			&& $this->userSession->isLoggedIn()
			&& !$this->userExists($_POST['shareWith'])
		) {
			$user = $this->userSession->getUser();
			if ($user) {
				$this->logger->info(
					"Creating guest account '{$_POST['shareWith']}', triggered "
					."by '{$user->getDisplayName()} ({$user->getUID()})' ");
			} else {
				$this->logger->error(
					"Not creating guest account '{$_POST['shareWith']}', "
					."uid is unknown");
				return;
			}
			$guest = new \OCA\Guests\Db\Guest($_POST['shareWith'], null);
			$this->mapper->insert($guest);
			$this->config->setUserValue($_POST['shareWith'], 'files', 'quota', 0);
			$this->config->setUserValue($_POST['shareWith'], 'settings', 'email', $_POST['shareWith']);
		}
	}
}
