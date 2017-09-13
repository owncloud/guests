<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license GPL-2.0
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

use OCA\Files_Sharing\Controller\ShareesController as FilesSharingShareesController;
use OCP\AppFramework\Http;
use OCP\IUser;
use OCP\Share;

class ShareesController extends FilesSharingShareesController {

	/**
	 * @param string $search
	 */
	protected function getUsers($search) {
		$this->result['users'] = $this->result['exact']['users'] = $users = [];

		$userGroups = [];
		if ($this->shareWithGroupOnly || $this->shareeEnumerationGroupMembers) {
			// Search in all the groups this user is part of
			$userGroups = $this->groupManager->getUserGroupIds($this->userSession->getUser());
			foreach ($userGroups as $userGroup) {
				$usersTmp = $this->groupManager->findUsersInGroup($userGroup, $search, $this->limit, $this->offset);
				foreach ($usersTmp as $uid => $user) {
					$users[$uid] = $user;
				}
			}
		} else {
			// Search in all users
			$usersTmp = $this->userManager->find($search, $this->limit, $this->offset);

			foreach ($usersTmp as $user) {
				$users[$user->getUID()] = $user;
			}
		}

		if (!$this->shareeEnumeration || sizeof($users) < $this->limit) {
			$this->reachedEndFor[] = 'users';
		}

		$foundUserById = false;
		$lowerSearch = strtolower($search);
		foreach ($users as $uid => $user) {
			$email = $user->getEMailAddress();
			$isGuest = (bool)$this->config->getUserValue(
				$uid,
				'owncloud',
				'isGuest'
			);
			$groupName = $this->config->getAppValue(
				'guests',
				'group',
				\OCA\Guests\GroupBackend::DEFAULT_NAME
			);
			$guestSuffix = $isGuest ?  " ($groupName)" : '';

			/* @var $user IUser */
			if (
				// Check if the uid is the same
				strtolower($uid) === $lowerSearch
				// Check if exact display name
				|| strtolower($user->getDisplayName()) === $lowerSearch
				// Check if exact first email
				|| strtolower($email) === $lowerSearch
				// Check for exact search term matches (when mail attributes configured as search terms + no enumeration)
				|| in_array($lowerSearch, array_map('strtolower', $user->getSearchTerms()))) {
				if (strtolower($uid) === $lowerSearch) {
					$foundUserById = true;
				}
				$this->result['exact']['users'][] = [
					'label' => $user->getDisplayName() . $guestSuffix,
					'value' => [
						'shareType' => Share::SHARE_TYPE_USER,
						'shareWith' => $uid,
						'email' => $email
					],
				];
			} else {
				$this->result['users'][] = [
					'label' => $user->getDisplayName() . $guestSuffix,
					'value' => [
						'shareType' => Share::SHARE_TYPE_USER,
						'shareWith' => $uid,
						'email' => $email
					],
				];
			}
		}

		if ($this->offset === 0 && !$foundUserById) {
			// On page one we try if the search result has a direct hit on the
			// user id and if so, we add that to the exact match list
			$user = $this->userManager->get($search);
			if ($user instanceof IUser) {
				$addUser = true;

				if ($this->shareWithGroupOnly) {
					// Only add, if we have a common group
					$commonGroups = array_intersect($userGroups, $this->groupManager->getUserGroupIds($user));
					$addUser = !empty($commonGroups);
				}

				if ($addUser) {
					array_push($this->result['exact']['users'], [
						'label' => $user->getDisplayName(),
						'value' => [
							'shareType' => Share::SHARE_TYPE_USER,
							'shareWith' => $user->getUID(),
						],
					]);
				}
			}
		}

		if (!$this->shareeEnumeration) {
			$this->result['users'] = [];
		}
	}
}
