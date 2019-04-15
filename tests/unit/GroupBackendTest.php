<?php
/**
 * ownCloud
 *
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
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

namespace OCA\Guests\Tests\Unit;

use OCA\Guests\GroupBackend;
use OCP\IConfig;
use Test\TestCase;

/**
 * Class GroupBackendTest
 *
 * @package OCA\Guests\Tests\Unit
 */
class GroupBackendTest extends TestCase {
	/**
	 * @var GroupBackend | \PHPUnit\Framework\MockObject\MockObject
	 */
	protected $groupbackend;

	/**
	 * @var array
	 */
	protected $members;

	/**
	 * @var IConfig | \PHPUnit\Framework\MockObject\MockObject
	 */
	protected $config;

	/**
	 * Set up the tests
	 *
	 * @return void
	 */
	public function setUp() {
		$this->config = $this->createMock(IConfig::class);
		$this->groupbackend = new GroupBackend(
			$this->config,
			GroupBackend::DEFAULT_NAME
		);
		$this->members = [];
		for ($i = 0 ; $i < 50; $i++) {
			$this->members[] = 'User' . ($i + 1);
		}
		$this->config
			->method('getUsersForUserValue')
			->willReturn($this->members);

		$this->config
			->method('getUserValue')
			->willReturnCallback([$this, 'getUserValue']);

		parent::setUp();
	}

	/**
	 * Callback function to check group membership
	 *
	 * @param string $userId
	 * @param string $arg2
	 * @param string $arg3
	 *
	 * @return int|string
	 */
	public function getUserValue($userId, $arg2, $arg3) {
		if ($arg2 !== 'owncloud' || $arg3 !== 'isGuest') {
			return '0';
		}
		if (\in_array($userId, $this->members, true)) {
			return '1';
		}
		return '0';
	}
	/**
	 * Test if Group exists
	 *
	 * @return void
	 */
	public function testGroupExists() {
		$groupExists = $this
			->groupbackend
			->groupExists(GroupBackend::DEFAULT_NAME);
		self::assertTrue($groupExists);

		// Call with wrong groupName
		$groupExists = $this
			->groupbackend
			->groupExists('not-' . GroupBackend::DEFAULT_NAME);
		self::assertFalse($groupExists);
	}

	/**
	 * Test Group Name
	 *
	 * @return void
	 */
	public function testGroupName() {
		$groupName = $this->groupbackend->getGroups();
		self::assertEquals([GroupBackend::DEFAULT_NAME], $groupName);
	}

	/**
	 * Test Actions
	 *
	 * @return void
	 */
	public function testActions() {
		$action = 0;
		$action |= GroupBackend::COUNT_USERS;
		self::assertTrue($this->groupbackend->implementsActions($action));
	}

	/**
	 * Test Group Membership
	 *
	 * @return void
	 */
	public function testGroupMembership() {
		$isMember = $this->groupbackend->inGroup(
			'User1',
			GroupBackend::DEFAULT_NAME
		);
		self::assertTrue($isMember);

		$isMember = $this->groupbackend->inGroup(
			'User20',
			GroupBackend::DEFAULT_NAME
		);
		self::assertTrue($isMember);

		$isMember = $this->groupbackend->inGroup(
			self::getUniqueID(),
			GroupBackend::DEFAULT_NAME
		);
		self::assertFalse($isMember);

		$isMember = $this->groupbackend->inGroup(
			'User21',
			'someothergroup'
		);
		self::assertFalse($isMember);
	}

	/**
	 * Test User Groups
	 *
	 * @return void
	 */
	public function testUserGroups() {
		$groups = $this->groupbackend->getUserGroups(
			$this->members[0]
		);
		self::assertEquals([GroupBackend::DEFAULT_NAME], $groups);

		$groups = $this->groupbackend->getUserGroups(
			$this->members[49]
		);
		self::assertEquals([GroupBackend::DEFAULT_NAME], $groups);

		$groups = $this->groupbackend->getUserGroups(
			self::getUniqueID()
		);
		self::assertEmpty($groups);
	}

	/**
	 * Using the wrong group name for search returns no users
	 *
	 * @return void
	 */
	public function testNoMembers() {
		$users = $this->groupbackend->usersInGroup(
			'not-' . GroupBackend::DEFAULT_NAME,
			'',
			50,
			0
		);
		self::assertEmpty($users);
	}

	/**
	 * Use the default parameters on usersInGroup()
	 *
	 * @return void
	 */
	public function testDefaultParameters() {
		$users = $this->groupbackend->usersInGroup(GroupBackend::DEFAULT_NAME, '', -1, 0);
		self::assertCount(50, $users);
		self::assertEquals(50, $this->groupbackend->countUsersInGroup());
	}

	/**
	 * Data for Search Test
	 *
	 * @return array
	 */
	public function searchData() {
		return [
			'search 1' => [
				'9',
				['User9', 'User19', 'User29', 'User39', 'User49']
			],
			'search 2' => [
				'ser2',
				[
					'User2', 'User20', 'User21', 'User22', 'User23', 'User24',
					'User25', 'User26', 'User27', 'User28', 'User29'
				]
			],
			'search 3' => [
				'User5',
				['User5', 'User50']
			],
			'search 4' => [
				'User11',
				['User11']
			],
			'search 5' => [
				'User111',
				[]
			]
		];
	}

	/**
	 * Get Group members like the search string
	 *
	 * @dataProvider searchData
	 *
	 * @param string $search
	 * @param array $expectedResult
	 *
	 * @return void
	 */
	public function testGetUsersSearch($search, $expectedResult) {
		$users = $this->groupbackend->usersInGroup(
			GroupBackend::DEFAULT_NAME,
			$search,
			-1,
			0
		);
		self::assertEquals(\array_values($expectedResult), \array_values($users));
	}

	/**
	 * Data for Paging Test
	 *
	 * @return array
	 */
	public function pagingData() {
		return [
			'page 1' => [0, 0, 0, null],
			'page 2' => [5, 0, 5, 'User1'],
			'page 3' => [5, 5, 5, 'User6'],
			'page 4' => [50, 5, 45, 'User6'],
			'page 5' => [50, 45, 5, 'User46'],
			'page 6' => [50, 0, 50, 'User1'],
			'page 7' => [50, 50, 0, null]
		];
	}

	/**
	 * Get Group Members with paginated search
	 *
	 * @dataProvider pagingData
	 *
	 * @param int $limit
	 * @param int $offset
	 * @param int $expectedResultCount
	 * @param string $expectedFirstValue
	 *
	 * @return void
	 */
	public function testGetUsersPaging($limit, $offset, $expectedResultCount, $expectedFirstValue) {
		$users = $this->groupbackend->usersInGroup(
			GroupBackend::DEFAULT_NAME,
			'',
			$limit,
			$offset
		);
		self::assertCount($expectedResultCount, $users);
		if (!empty($users)) {
			self::assertEquals($expectedFirstValue, $users[0]);
		}
	}
}
