<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

namespace OCA\Guests\Controller;

use OCA\Guests\AppWhitelist;
use OCA\Guests\GroupBackend;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;

/**
 * Class SettingsController is used to handle configuration changes on the
 * settings page
 *
 * @package OCA\Guests\Controller
 */
class SettingsController extends Controller {

	/**
	 * @var string
	 */
	private $userId;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IL10N
	 */
	private $l10n;

	/** @var IAppManager */
	private $appManager;

	/** @var IGroupManager */
	private $groupManager;

	public function __construct($AppName, IRequest $request, $UserId, IConfig $config,
								IL10N $l10n, IAppManager $appManager, IGroupManager $groupManager) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->config = $config;
		$this->l10n = $l10n;
		$this->appManager = $appManager;
		$this->groupManager = $groupManager;
	}

	/**
	 * AJAX handler for getting the config
	 *
	 * @return DataResponse with the current config
	 */
	public function getConfig() {
		$useWhitelist = $this->config->getAppValue('guests', 'usewhitelist', true);
		if ($useWhitelist === 'true' || $useWhitelist === true) {
			$useWhitelist = true;
		} else {
			$useWhitelist = false;
		}

		$groupName = $this->config->getAppValue('guests', 'group', GroupBackend::DEFAULT_NAME);
		$appAccessService = $this->appManager->getAppsAccessService();
		$whitelistedApps = $appAccessService->getWhitelistedAppsForGroup($this->groupManager->get($groupName));
		return new DataResponse([
			'group' => $groupName,
			'useWhitelist' => $useWhitelist,
			'whitelist' => $whitelistedApps,
		]);
	}
	/**
	 * AJAX handler for setting the config
	 *
	 * @param $conditions string[]
	 * @param $group string
	 * @param $useWhitelist bool
	 * @param $whitelist string[]
	 * @return DataResponse
	 */
	public function setConfig($conditions, $group, $useWhitelist, $whitelist) {
		$newWhitelist = [];
		if (empty($group)) {
			return new DataResponse([
				'status' => 'error',
				'data' => [
					'message' => $this->l10n->t('Group name must not be empty.')
				],
			]);
		}
		$oldGid = $this->config->getAppValue('guests', 'group', GroupBackend::DEFAULT_NAME);
		if ($group !== $oldGid) {
			$this->config->setAppValue('guests', 'group', $group);
			/**
			 * Delete the old entry, else it becomes stale information.
			 */
			$this->appManager->getAppsAccessService()->wipeWhitelistedAppsForGroup($oldGid);
		}

		foreach ($whitelist as $app) {
			$newWhitelist[] = \trim($app);
		}
		$newWhitelist = \join(',', $newWhitelist);

		if (!$this->setWhitelistApps(\explode(',', $newWhitelist))) {
			return new DataResponse([
				'status' => 'failure',
				'data' => [
					'message' => $this->l10n->t('Failed to update apps to be whitelisted.')
				],
			]);
		}

		return new DataResponse([
			'status' => 'success',
			'data' => [
				'message' => $this->l10n->t('Saved')
			],
		]);
	}

	/**
	 * AJAX handler for getting whitelisted apps
	 *
	 * @NoAdminRequired
	 * @return array whitelisted apps
	 */
	public function getWhitelist() {
		$gid = $this->config->getAppValue('guests', 'group', GroupBackend::DEFAULT_NAME);
		$group = $this->groupManager->get($gid);
		return [
			'isGuest' => false,
			'enabled' => $this->config->getAppValue('guests', 'usewhitelist', 'true') === 'true',
			'apps' => $this->appManager->getAppsAccessService()->getWhitelistedAppsForGroup($group)
		];
	}

	/**
	 * AJAX handler for resetting the whitelisted apps
	 *
	 * @NoAdminRequired
	 * @return DataResponse with the reset whitelist
	 */
	public function resetWhitelist() {
		$resetWhiteListApps = AppWhitelist::CORE_WHITELIST . ',' . AppWhitelist::DEFAULT_WHITELIST;
		if (!$this->setWhitelistApps(\explode(',', $resetWhiteListApps))) {
			return new DataResponse([
				'status' => 'failure',
				'data' => [
					'message' => 'Failed to update the apps'
				],
			]);
		}

		return new DataResponse([
			'whitelist' => \explode(',', $resetWhiteListApps),
		]);
	}

	/**
	 * Insert or update whitelisted apps
	 * This method would be called if the whitelisted apps are updated/reset to
	 * default whitelist.
	 *
	 * @param array $apps
	 * @return bool, true if the apps are set/updated else false
	 */
	private function setWhitelistApps(array $apps) {
		$gid = $this->config->getAppValue('guests', 'group', GroupBackend::DEFAULT_NAME);
		$group = $this->groupManager->get($gid);
		$appAccessService = $this->appManager->getAppsAccessService();
		return $appAccessService->setWhitelistedAppsForGroup($group, $apps);
	}
}
