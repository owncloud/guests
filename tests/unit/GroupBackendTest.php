<?php
/**
 * @author Piotr Mrowczynski <piotr@owncoud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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
namespace OCA\Guests\Tests\unit;

use OCA\Guests\GroupBackend;
use OCA\Guests\GuestsHandler;
use OCP\GroupInterface;

/**
 * Class GroupBackendTest
 *
 * @package OCA\Guests\Tests\Unit
 */
class GroupBackendTest extends \Test\TestCase {

	const GUEST_GID = 'guest_app';
	const DEFAULT_DISPLAY_NAME = 'Guests';

	/**
	 * @var GuestsHandler|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $handler;

	/**
	 * @var GroupBackend
	 */
	private $backend;

	public function setUp() {
		$this->handler = $this->createMock(GuestsHandler::class);
		$this->handler->expects($this->any())
			->method('getGuestsGID')
			->willReturn(self::GUEST_GID);
		$this->handler->expects($this->any())
			->method('getGuestsDisplayName')
			->willReturn(self::DEFAULT_DISPLAY_NAME);
		$this->backend = new GroupBackend($this->handler);
		parent::setUp();
	}

	public function testImplementsAction() {
		$this->assertTrue($this->backend->implementsActions(GroupInterface::GROUP_DETAILS));
		$this->assertTrue($this->backend->implementsActions(GroupInterface::COUNT_USERS));

		$this->assertFalse($this->backend->implementsActions(GroupInterface::CREATE_GROUP));
		$this->assertFalse($this->backend->implementsActions(GroupInterface::DELETE_GROUP));
		$this->assertFalse($this->backend->implementsActions(GroupInterface::ADD_TO_GROUP));
		$this->assertFalse($this->backend->implementsActions(GroupInterface::REMOVE_FROM_GROUP));
	}

	public function testInGroup() {
		$this->handler->expects($this->any())
			->method('getGuests')
			->willReturn(['user1']);

		$this->assertTrue($this->backend->inGroup('user1', self::GUEST_GID));
		$this->assertFalse($this->backend->inGroup('user2', self::GUEST_GID));
		$this->assertFalse($this->backend->inGroup('user1', 'one'));
	}

	public function testGetGroupDetails() {
		$details = $this->backend->getGroupDetails('wrong');
		$this->assertEquals(null, $details);

		$details = $this->backend->getGroupDetails(self::GUEST_GID);
		$this->assertEquals(self::GUEST_GID, $details['gid']);
		$this->assertEquals(self::DEFAULT_DISPLAY_NAME, $details['displayName']);
	}

	public function testGetUserGroups() {
		$this->handler->expects($this->any())
			->method('getGuests')
			->willReturn(['user1', 'user2']);

		$this->assertEquals([self::GUEST_GID], $this->backend->getUserGroups('user1'));
		$this->assertEquals([self::GUEST_GID], $this->backend->getUserGroups('user2'));
		$this->assertEquals([], $this->backend->getUserGroups('user3'));
	}

	public function testGetGroups() {
		$this->assertEquals([self::GUEST_GID], $this->backend->getGroups());
	}

	public function testGroupExists() {
		$this->assertTrue($this->backend->groupExists(self::GUEST_GID));
		$this->assertFalse($this->backend->groupExists('wrong'));
	}

	public function testUsersInGroup() {
		$users = ['user1', 'user2'];
		$this->handler->expects($this->any())
			->method('getGuests')
			->willReturn($users);

		$this->assertEquals($users, $this->backend->usersInGroup(self::GUEST_GID));
		$this->assertEquals([], $this->backend->usersInGroup('wrong'));
	}

	public function testCountUsersInGroup() {
		$users = ['user1', 'user2'];
		$this->handler->expects($this->any())
			->method('getGuests')
			->willReturn($users);

		$this->assertEquals(2, $this->backend->countUsersInGroup());
	}

	public function testIsVisibleForScope() {
		// Test it is visible everywhere, except sharing
		$this->assertFalse($this->backend->isVisibleForScope('sharing'));
		$this->assertTrue($this->backend->isVisibleForScope(''));
	}
}
