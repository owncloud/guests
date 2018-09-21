<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
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

namespace OCA\Guests\Tests;

use OCA\Guests\Controller\SettingsController;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Test\TestCase;

class SettingsControllerTest extends TestCase {
	/** @var string */
	private $appName;

	/** @var IRequest | \PHPUnit_Framework_MockObject_MockObject */
	private $request;

	/** @var string */
	private $userId;

	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var IL10N | \PHPUnit_Framework_MockObject_MockObject */
	private $l10n;

	/** @var EventDispatcherInterface | \PHPUnit_Framework_MockObject_MockObject */
	private $eventDispatcher;

	protected function setUp() {
		parent::setUp();

		$this->appName = 'guests';
		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
	}

	public function testSetConfigGroupRename() {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturn('group1');

		$this->eventDispatcher->expects($this->once())
			->method('dispatch')
			->with('guest.grouprename', new GenericEvent(null, ['oldgroupname' => 'group1', 'newgroupname' => 'group2']));
		$controller = new SettingsController($this->appName, $this->request,
			'user1', $this->config, $this->l10n, $this->eventDispatcher);
		$result = $controller->setConfig([''], 'group2', false, ['app1', 'app2', 'app3']);
		$this->assertEquals(200, $result->getStatus());
		$this->assertEquals(['status' => 'success', 'data' => ['message' => null]], $result->getData());
	}
}
