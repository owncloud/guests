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

use OCA\Guests\GuestsHandler;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class GuestHandlerTest
 *
 * @package OCA\Guests\Tests\Unit
 */
class GuestHandlerTest extends \Test\TestCase {

	/**
	 * @var GuestsHandler
	 */
	private $handler;

	/**
	 * @var IUserManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $userManager;

	/**
	 * @var IMailer|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $mailer;

	/**
	 * @var ISecureRandom|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $secureRandom;

	/**
	 * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $eventDispatcher;

	/**
	 * @var IConfig|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $config;

	public function setUp() {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
		$this->handler = new GuestsHandler(
			$this->config,
			$this->userManager,
			$this->mailer,
			$this->secureRandom,
			$this->eventDispatcher
		);
	}

	public function testStaticLegacy() {
		$instance = $this->handler->createForStaticLegacyCode();
		$this->assertInstanceOf(GuestsHandler::class, $instance);
	}

	public function testIsGuest() {
		$this->config->expects($this->at(0))
			->method('getUserValue')
			->with('user1', 'owncloud', 'isGuest', false)
			->willReturn(true);
		$this->config->expects($this->at(1))
			->method('getUserValue')
			->with('user2', 'owncloud', 'isGuest', false)
			->willReturn(false);

		$this->assertTrue($this->handler->isGuest('user1'));
		$this->assertFalse($this->handler->isGuest('user2'));
	}

	public function testBase() {
		$this->assertEquals('guest_app', $this->handler->getGuestsGID());
	}

	public function testDisplayname() {
		$displayName = 'Guests';
		$this->config->expects($this->at(0))
			->method('getAppValue')
			->with('guests', 'group', $displayName)
			->willReturn($displayName);
		$this->config->expects($this->at(1))
			->method('setAppValue')
			->with('guests', 'group', $displayName);

		$this->assertEquals($displayName, $this->handler->getGuestsDisplayName());
		$this->handler->setGuestsDisplayName($displayName);
	}

	public function testGetGuests() {
		$users = ['user1', 'user2'];
		$this->config->expects($this->at(0))
			->method('getUsersForUserValue')
			->with('owncloud', 'isGuest', '1')
			->willReturn($users);

		$this->assertEquals($users, $this->handler->getGuests());
	}

	public function testCreateToken() {
		$msg = $this->createMock(ISecureRandom::class);
		$msg->expects($this->at(0))
			->method('generate')
			->willReturn('something');
		$this->secureRandom->expects($this->at(0))
			->method('getMediumStrengthGenerator')
			->willReturn($msg);

		$this->assertEquals('something', $this->handler->createToken());
	}

	private function initGuest($email, $regToken) {
		$uid = strtolower($email);

		$this->config->expects($this->at(0))
			->method('getUserValue')
			->with($uid, 'owncloud', 'isGuest', false)
			->willReturn(true);
		$this->config->expects($this->at(1))
			->method('getUserValue')
			->with($uid, 'guests', 'registerToken')
			->willReturn($regToken);

		$this->mailer->expects($this->at(0))
			->method('validateMailAddress')
			->with($email)
			->willReturn(true);
	}

	public function testValidateGuest() {
		$email = 'User1@email.com';
		$regToken = 'token';
		$this->initGuest($email, $regToken);

		$this->assertTrue($this->handler->validateGuest($email, 'token'));
	}

	public function testValidateGuestFailEmail() {
		$email = 'User1@email.com';

		$this->mailer->expects($this->any())
			->method('validateMailAddress')
			->with($email)
			->willReturn(false);

		$this->assertFalse($this->handler->validateGuest($email, 'token'));
		$this->assertFalse($this->handler->validateGuest('', 'token'));

		$this->assertFalse($this->handler->createGuest($email, $email, 'token'));
		$this->assertFalse($this->handler->createGuest('', $email, 'token'));
	}

	public function testValidateGuestFailIsGuest() {
		$email = 'User1@email.com';
		$uid = strtolower($email);

		$this->mailer->expects($this->at(0))
			->method('validateMailAddress')
			->with($email)
			->willReturn(true);

		$this->config->expects($this->at(0))
			->method('getUserValue')
			->with($uid, 'owncloud', 'isGuest', false)
			->willReturn(false);

		$this->assertFalse($this->handler->validateGuest($email, 'token'));
	}

	public function testUpdateGuest() {
		$email = 'User1@email.com';
		$regToken = 'token';
		$this->initGuest($email, $regToken);

		$user = $this->createMock(IUser::class);
		$user->expects($this->at(0))
			->method('setPassword')
			->with('pass');

		$this->userManager->expects($this->at(0))
			->method('get')
			->with(strtolower($email))
			->willReturn($user);

		$this->config->expects($this->any())
			->method('deleteUserValue')
			->with(strtolower($email), 'guests', 'registerToken')
			->willReturn(true);

		$this->assertTrue($this->handler->updateGuest($email,'pass', 'token'));
	}

	public function testUpdateGuestFailToken() {
		$email = 'User1@email.com';
		$regToken = 'token';
		$this->initGuest($email, $regToken);

		$this->assertFalse($this->handler->updateGuest($email,'pass', 'wrong_token'));
	}

	public function testCreateGuestFailUser() {
		$email = 'User1@email.com';

		$this->mailer->expects($this->any())
			->method('validateMailAddress')
			->with($email)
			->willReturn(true);

		$this->userManager->expects($this->at(0))
			->method('userExists')
			->with(strtolower($email))
			->willReturn(true);
		$this->assertFalse($this->handler->createGuest($email, $email, 'token'));

		$this->userManager->expects($this->at(0))
			->method('userExists')
			->with(strtolower($email))
			->willReturn(false);

		// There are users with this email, so fail
		$this->userManager->expects($this->at(1))
			->method('getByEmail')
			->willReturn(['User1@email.com']);
		$this->assertFalse($this->handler->createGuest($email, $email, 'token'));
	}

	public function testCreateGuest() {
		$email = 'User1@email.com';

		$this->mailer->expects($this->any())
			->method('validateMailAddress')
			->with($email)
			->willReturn(true);

		$this->userManager->expects($this->at(0))
			->method('userExists')
			->with(strtolower($email))
			->willReturn(false);

		$this->userManager->expects($this->at(1))
			->method('getByEmail')
			->willReturn([]);

		$this->secureRandom->expects($this->at(0))
			->method('generate')
			->willReturn('pass');

		$user = $this->createMock(IUser::class);
		$user->expects($this->at(0))
			->method('setEMailAddress')
			->with($email);
		$user->expects($this->at(1))
			->method('setDisplayName')
			->with($email);
		$this->userManager->expects($this->at(2))
			->method('createUser')
			->with(strtolower($email), 'pass')
			->willReturn($user);

		$this->config->expects($this->at(0))
			->method('setUserValue')
			->with(strtolower($email), 'guests', 'registerToken', 'token');
		$this->config->expects($this->at(1))
			->method('setUserValue')
			->withAnyParameters(strtolower($email), 'guests', 'created');
		$this->config->expects($this->at(2))
			->method('setUserValue')
			->with(strtolower($email), 'owncloud', 'isGuest', '1');

		$this->assertTrue($this->handler->createGuest($email, $email, 'token'));
	}
}
