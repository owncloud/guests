<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Guests\Tests\Unit\Controllers;

use OCA\Guests\Controller\GuestShareController;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\Share\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Files\IRootFolder;
use OCP\IUserSession;

/**
 * @group DB
 *
 * @package OCA\Guests\Tests\Unit\Controllers
 */
class GuestShareControllerTest extends \Test\TestCase {

	/** @var GuestShareController */
	private $controller;
	/** @var IManager | \PHPUnit_Framework_MockObject_MockObject */
	private $shareManager;
	/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var IGroupManager | \PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;
	/** @var IRootFolder | \PHPUnit_Framework_MockObject_MockObject */
	private $rootFolder;
	/** @var IRequest | \PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IUser | \PHPUnit_Framework_MockObject_MockObject */
	private $currentUser;

	protected function setUp() {
		parent::setUp();
		$this->appName = 'files_sharing';

		$this->shareManager = $this->createMock(IManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		// Create a dummy user
		$this->currentUser = $this->createMock(IUser::class);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->currentUser);

		$this->controller = new GuestShareController(
			$this->shareManager,
			$this->groupManager,
			$this->userManager,
			$this->request,
			$this->rootFolder,
			$this->urlGenerator,
			$userSession,
			$this->createMock(IL10N::class),
			$this->config
		);
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testTodo() {
		// TODO: mock share manager, etc

		$expected = new \OC\OCS\Result([]);
		$this->assertEquals($expected, $this->controller->getShares());
	}
}
