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

namespace OCA\guests\Migrations;

use OCA\Guests\AppWhitelist;
use OCA\Guests\GroupBackend;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\Migration\ISimpleMigration;
use OCP\Migration\IOutput;

/**
 * Move the whitelisted data from the appconfig to appaccess, if its there.
 * If no data is available then just update the appaccess with default whitelisted
 * apps data.
 */
class Version20191115135305 implements ISimpleMigration {
	/** @var IAppManager */
	private $appManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IConfig */
	private $config;

	/**
	 * Version20191115135305 constructor.
	 *
	 * @param IAppManager $appManager
	 * @param IGroupManager $groupManager
	 * @param IConfig $config
	 */
	public function __construct(IAppManager $appManager, IGroupManager $groupManager, IConfig $config) {
		$this->appManager = $appManager;
		$this->groupManager = $groupManager;
		$this->config = $config;
	}

	/**
	 *
	 * @param IOutput $out
	 *
	 */
	public function run(IOutput $out) {
		//Get the whitelisted apps from the appconfig
		$whitelistedApps = AppWhitelist::CORE_WHITELIST. ',' . $this->config->getAppValue(
			'guests',
			'whitelist',
			AppWhitelist::DEFAULT_WHITELIST);

		$appAccessService = $this->appManager->getAppsAccessService();

		/**
		 * Now copy the data from the appconfig to appaccess table.
		 */
		$groupObject = $this->groupManager->get(GroupBackend::DEFAULT_NAME);
		$appAccessService->setWhitelistedAppsForGroup($groupObject, \explode(',', $whitelistedApps));

		// Last step delete the data from the appconfig table
		$this->config->deleteAppValue('guests', 'whitelist');
	}
}
