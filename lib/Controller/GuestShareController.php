<?php
/**
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @author Michael Jobst <mjobst+github@tecratech.de>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Guests\Controller;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCA\Files_Sharing\API\Share20OCS;
use OCP\IUserSession;

class GuestShareController extends Share20OCS {

	/**
	 * Constructor.
	 *
	 * @param IManager $shareManager
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param IRequest $request
	 * @param IRootFolder $rootFolder
	 * @param IURLGenerator $urlGenerator
	 * @param IUserSession $userSession
	 * @param IL10N $l10n
	 * @param IConfig $config
	 */
	public function __construct(
			IManager $shareManager,
			IGroupManager $groupManager,
			IUserManager $userManager,
			IRequest $request,
			IRootFolder $rootFolder,
			IURLGenerator $urlGenerator,
			IUserSession $userSession,
			IL10N $l10n,
			IConfig $config
	) {
		$this->shareManager = $shareManager;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->request = $request;
		$this->rootFolder = $rootFolder;
		$this->urlGenerator = $urlGenerator;
		$this->userSession = $userSession;
		$this->l = $l10n;
		$this->config = $config;
	}

	/**
	 * Convert an IShare to an array for OCS output
	 *
	 * @param \OCP\Share\IShare $share
	 * @return array
	 * @throws NotFoundException In case the node can't be resolved.
	 */
	protected function formatShare(\OCP\Share\IShare $share, $received = false) {
		$sharedBy = $this->userManager->get($share->getSharedBy());
		$shareOwner = $this->userManager->get($share->getShareOwner());

		$result = [
			'id' => $share->getId(),
			'share_type' => $share->getShareType(),
			'uid_owner' => $share->getSharedBy(),
			'displayname_owner' => $sharedBy !== null ? $sharedBy->getDisplayName() : $share->getSharedBy(),
			'permissions' => $share->getPermissions(),
			'stime' => $share->getShareTime()->getTimestamp(),
			'parent' => null,
			'expiration' => null,
			'token' => null,
			'uid_file_owner' => $share->getShareOwner(),
			'displayname_file_owner' => $shareOwner !== null ? $shareOwner->getDisplayName() : $share->getShareOwner(),
		];

		$userFolder = $this->rootFolder->getUserFolder($this->userSession->getUser()->getUID());
		$nodes = $userFolder->getById($share->getNodeId());

		if (empty($nodes)) {
			throw new NotFoundException();
		}

		$node = $nodes[0];

		$result['path'] = $userFolder->getRelativePath($node->getPath());
		if ($node instanceOf \OCP\Files\Folder) {
			$result['item_type'] = 'folder';
		} else {
			$result['item_type'] = 'file';
		}
		$result['mimetype'] = $node->getMimeType();
		$result['storage_id'] = $node->getStorage()->getId();
		$result['storage'] = $node->getStorage()->getCache()->getNumericStorageId();
		$result['item_source'] = $node->getId();
		$result['file_source'] = $node->getId();
		$result['file_parent'] = $node->getParent()->getId();
		$result['file_target'] = $share->getTarget();

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			$sharedWith = $this->userManager->get($share->getSharedWith());
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = $sharedWith !== null ? $sharedWith->getDisplayName() : $share->getSharedWith();
		}

		$result['mail_send'] = $share->getMailSend() ? 1 : 0;

		return $result;
	}

	/**
	 * The getShares function.
	 *
	 * - Get shares by the current user
	 * - Get shares for a specific path (?path=...)
	 * - Get all shares in a folder (?subfiles=true&path=..)
	 *
	 * @return \OC\OCS\Result
	 */
	public function getShares() {
		if (!$this->shareManager->shareApiEnabled()) {
			return new \OC\OCS\Result();
		}

		$subfiles = $this->request->getParam('subfiles');
		$path = $this->request->getParam('path', null);

		$includeTags = $this->request->getParam('include_tags', false);

		if ($path !== null) {
			$userFolder = $this->rootFolder->getUserFolder($this->userSession->getUser()->getUID());
			try {
				$path = $userFolder->get($path);
				$path->lock(ILockingProvider::LOCK_SHARED);
			} catch (\OCP\Files\NotFoundException $e) {
				return new \OC\OCS\Result(null, 404, $this->l->t('Wrong path, file/folder doesn\'t exist'));
			} catch (LockedException $e) {
				return new \OC\OCS\Result(null, 404, $this->l->t('Could not lock path'));
			}
		}

		if ($subfiles === 'true') {
			$result = $this->getSharesInDir($path);
			if ($path !== null) {
				$path->unlock(ILockingProvider::LOCK_SHARED);
			}
			return $result;
		}

		$reshares = false;

		// Get all shares of type SHARE_TYPE_USER
		$shares = $this->shareManager->getSharesBy($this->userSession->getUser()->getUID(), \OCP\Share::SHARE_TYPE_USER, $path, $reshares, -1, 0);

		$formatted = [];
		foreach ($shares as $share) {
			try {
				// only show when shared with at least one guest
				if (!$this->hasGuests($share)) {
					continue;
				}
				$formatted[] = $this->formatShare($share);
			} catch (NotFoundException $e) {
				//Ignore share
			}
		}

		if ($includeTags) {
			$formatted = \OCA\Files\Helper::populateTags($formatted, 'file_source');
		}

		if ($path !== null) {
			$path->unlock(ILockingProvider::LOCK_SHARED);
		}

		return new \OC\OCS\Result($formatted);
	}

	/**
	 * Check if there are guest users in a share
	 *
	 * @param unknown $share
	 * @return boolean
	 */
	private function hasGuests($share) {
		$hasGuests = false;
		$users = $share->getSharedWith();
		if (!is_array($users)) {
			$users = array($users);
		}
		foreach ($users as $uid) {
			$isGuest = (bool)$this->config->getUserValue(
				$uid,
				'owncloud',
				'isGuest'
			);
			if ($isGuest) {
				return true;
			}
		}
		return false;
	}

}
