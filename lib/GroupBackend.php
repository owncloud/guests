<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
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

use OCP\GroupInterface;

/**
 * Provides a virtual (not existing in the database) group for guest users.
 * Members of this group are determined by the user value "isGuest" in oc_preferences.
 *
 * @package OCA\Guests
 */
class GroupBackend implements GroupInterface {

	/**
	 * @var GuestsHandler
	 */
	private $handler;

	private $guestMembers = [];

	protected $possibleActions = [
		self::COUNT_USERS => 'countUsersInGroup',
		self::GROUP_DETAILS => 'getGroupDetails'
	];

	/**
	 * GroupBackend constructor.
	 *
	 * @param GuestsHandler $handler
	 */
	public function __construct(
		GuestsHandler $handler
	) {
		$this->handler = $handler;
	}

	/**
	 * Returns the info for a given group.
	 *
	 * @param string $gid group id
	 * @return array|null group info or null if not found
	 */
	public function getGroupDetails($gid) {
		if ($gid === $this->handler->getGuestsGID()) {
			return [
				'gid' => $this->handler->getGuestsGID(),
				'displayName' => $this->handler->getGuestsDisplayName(),
			];
		}
		return null;
	}

	/**
	 * Get all supported actions
	 * @return int bitwise-or'ed actions
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC\Group\Backend::CREATE_GROUP etc.
	 */
	public function getSupportedActions() {
		$actions = 0;
		foreach($this->possibleActions AS $action => $methodName) {
			if (method_exists($this, $methodName)) {
				$actions |= $action;
			}
		}

		return $actions;
	}

	/**
	 * Check if backend implements actions
	 * @param int $actions bitwise-or'ed actions
	 * @return bool
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC\Group\Backend::CREATE_GROUP etc.
	 */
	public function implementsActions($actions) {
		return (bool)($this->getSupportedActions() & $actions);
	}


	/**
	 * is user in group?
	 *
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 * @since 4.5.0
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid) {
		return in_array($uid, $this->getMembers()) && $gid === $this->handler->getGuestsGID();

	}

	/**
	 * Get all groups a user belongs to
	 *
	 * @param string $uid Name of the user
	 * @return array an array of group names
	 * @since 4.5.0
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		if (in_array($uid, $this->getMembers())) {
			return [$this->handler->getGuestsGID()];
		}

		return [];
	}

	/**
	 * get a list of all groups
	 *
	 * TODO: add support for search, this implementation is wrong
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of group names
	 * @since 4.5.0
	 *
	 * Returns a list with all groups
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		return [$this->handler->getGuestsGID()];
	}

	/**
	 * check if a group exists
	 *
	 * @param string $gid
	 * @return bool
	 * @since 4.5.0
	 */
	public function groupExists($gid) {
		return $gid === $this->handler->getGuestsGID();
	}

	/**
	 * get a list of all users in a group
	 *
	 * TODO: add support for limit and offset, otherwise the implementation is wrong and can cause bugs
	 *
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of user ids
	 * @since 4.5.0
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		if ($gid === $this->handler->getGuestsGID()) {
			return $this->getMembers();
		}

		return [];
	}

	/**
	 * @return int
	 */
	public function countUsersInGroup() {
		return count($this->getMembers());
	}

	/**
	 * Returns whether the groups are visible for a given scope.
	 *
	 * @param string|null $scope scope string
	 * @return bool true if searchable, false otherwise
	 *
	 * @since 10.0.0
	 */
	public function isVisibleForScope($scope) {
		return $scope !== 'sharing';
	}

	private function getMembers() {
		if (empty($this->guestMembers)) {
			$this->guestMembers = $this->handler->getGuests();
		}

		return $this->guestMembers;
	}
}
