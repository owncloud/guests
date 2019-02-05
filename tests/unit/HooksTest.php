<?php
/**
 * ownCloud
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 * @copyright (C) 2019 ownCloud GmbH
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

use OCA\Guests\Hooks;
use OCA\Guests\Mail;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Test\TestCase;

/**
 * Class HooksTest
 *
 * @package OCA\Guests\Tests\Unit
 */
class HooksTest extends TestCase {
	const GUEST_UID = 'me@example.org';

	/**
	 * @var ILogger | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $logger;

	/**
	 * @var IUserSession | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $userSession;

	/**
	 * @var Mail | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $mail;

	/**
	 * @var IConfig | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $config;

	/**
	 * @var Hooks
	 */
	private $hooks;

	public function setUp() {
		$this->logger = $this->createMock(ILogger::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->mail = $this->createMock(Mail::class);
		$this->config = $this->createMock(IConfig::class);
		$this->hooks = new Hooks(
			$this->logger, $this->userSession, $this->mail, $this->config
		);
	}

	public function testUnsupportedShareType() {
		$shareMock = $this->createMock(IShare::class);
		$shareMock->method('getNodeType')->willReturn('key');

		$this->logger->expects($this->once())->method('debug')->willReturnCallback(
			function ($message, $params) use ($shareMock) {
				$itemType = $shareMock->getNodeType();
				$this->assertEquals("ignoring share for itemType '$itemType'", $message);
			}
		);

		$this->hooks->handlePostShare($shareMock);
	}

	public function testNonGuestUser() {
		$shareMock = $this->createMock(IShare::class);
		$shareMock->method('getNodeType')->willReturn('file');
		$shareMock->method('getSharedWith')->willReturn(self::GUEST_UID);

		$this->config->expects($this->once())->method('getUserValue')
			->with(self::GUEST_UID, 'owncloud', 'isGuest', false)
			->willReturn(false);

		$this->logger->expects($this->once())->method('debug')->willReturnCallback(
			function ($message, $params) use ($shareMock) {
				$shareWith = $shareMock->getSharedWith();
				$this->assertEquals("ignoring user '$shareWith', not a guest", $message);
			}
		);

		$this->hooks->handlePostShare($shareMock);
	}

	public function testPostShareHookWithNoUser() {
		$shareMock = $this->createMock(IShare::class);
		$shareMock->method('getNodeType')->willReturn('file');
		$shareMock->method('getSharedWith')->willReturn(self::GUEST_UID);

		$this->config->expects($this->once())->method('getUserValue')
			->with(self::GUEST_UID, 'owncloud', 'isGuest', false)
			->willReturn(true);

		$message = '';
		try {
			$this->hooks->handlePostShare($shareMock);
		} catch (\Exception $e) {
			$message = $e->getMessage();
		}

		$this->assertEquals('post_share hook triggered without user in session', $message);
	}

	public function testPostShareHookForRegisteredGuest() {
		$shareMock = $this->createMock(IShare::class);
		$shareMock->method('getNodeType')->willReturn('file');
		$shareMock->method('getSharedWith')->willReturn(self::GUEST_UID);

		$this->config->method('getUserValue')
			->withConsecutive(
				[self::GUEST_UID, 'owncloud', 'isGuest', false],
				[self::GUEST_UID, 'guests', 'registerToken', null]
			)
			->willReturnOnConsecutiveCalls(true, null);

		$userMock = $this->createMock(IUser::class);
		$this->userSession->method('getUser')->willReturn($userMock);

		$this->mail->expects($this->never())->method('sendGuestInviteMail');

		$this->hooks->handlePostShare($shareMock);
	}

	public function testPostShareHookForNewGuest() {
		$shareMock = $this->createMock(IShare::class);
		$shareMock->method('getNodeType')->willReturn('file');
		$shareMock->method('getSharedWith')->willReturn(self::GUEST_UID);

		$this->config->method('getUserValue')
			->withConsecutive(
				[self::GUEST_UID, 'owncloud', 'isGuest', false],
				[self::GUEST_UID, 'guests', 'registerToken', null]
			)
			->willReturnOnConsecutiveCalls(true, 'token');

		$userMock = $this->createMock(IUser::class);
		$userMock->method('getUID')->willReturn(self::GUEST_UID);
		$this->userSession->method('getUser')->willReturn($userMock);

		$this->mail->expects($this->once())
			->method('sendGuestInviteMail')
			->with($shareMock, self::GUEST_UID, 'token');

		$this->hooks->handlePostShare($shareMock);
	}
}
