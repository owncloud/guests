<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

namespace OCA\Guests\Tests\Unit;

use OC\App\AppAccessService;
use OC\Migration\ConsoleOutput;
use OCA\guests\Migrations\Version20191115135305;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

//FixMe Couldn't find a better way. The autoloader was not picking Version20191115135305.php
require_once(\str_replace('/guests/tests/unit', '/guests/appinfo/Migrations', __DIR__) .  '/Version20191115135305.php');

/**
 * Class Version20191115135305Test
 *
 * @package OCA\Guests\Tests\Unit
 */
class Version20191115135305Test extends TestCase {
	/** @var IAppManager | \PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var IGroupManager | \PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;
	/** @var IConfig | \PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var Version20191115135305 */
	private $migrationVersionObj;

	protected function setUp() {
		parent::setUp();
		$this->appManager = $this->createMock(IAppManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->migrationVersionObj = new Version20191115135305($this->appManager, $this->groupManager, $this->config);
	}

	public function testRunMigration() {
		$outputInterface = $this->createMock(OutputInterface::class);
		$ioutput = new ConsoleOutput($outputInterface);

		$this->config->method('getAppValue')
			->willReturn('app1, app2, app3');
		$this->config->expects($this->once())
			->method('deleteAppValue')
			->with('guests', 'whitelist');

		$appAccessService = $this->createMock(AppAccessService::class);
		$appAccessService->expects($this->once())
			->method('setWhitelistedAppsForGroup');
		$this->appManager->method('getAppsAccessService')
			->willReturn($appAccessService);

		$groupObj = $this->createMock(IGroup::class);
		$this->groupManager->method('get')
			->willReturn($groupObj);

		$this->migrationVersionObj->run($ioutput);
	}
}
