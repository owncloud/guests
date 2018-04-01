<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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

namespace OCA\Guests\Controller;

use OCA\Guests\AppWhitelist;
use OCA\Guests\GuestsHandler;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
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
	 * @var IL10N
	 */
	private $l10n;

	/**
	 * @var AppWhitelist
	 */
	private $appWhitelist;

	/**
	 * @var GuestsHandler
	 */
	private $handler;

	public function __construct(
		$AppName,
		IRequest $request,
		AppWhitelist $appWhitelist,
		GuestsHandler $handler,
		IL10N $l10n
	) {
		parent::__construct($AppName, $request);
		$this->appWhitelist = $appWhitelist;
		$this->handler = $handler;
		$this->l10n = $l10n;
	}

	/**
	 * AJAX handler for getting the config
	 *
	 * @return DataResponse with the current config
	 */
	public function getConfig() {
		return new DataResponse([
			'group' => $this->handler->getGuestsDisplayName(),
			'useWhitelist' => $this->appWhitelist->isWhitelistEnabled(),
			'whitelist' => $this->appWhitelist->getAppWhitelist(),
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
		if (empty($group)) {
			return new DataResponse([
				'status' => 'error',
				'data' => [
					'message' => $this->l10n->t('Group name must not be empty.')
				],
			]);
		}

		$newWhitelist = [];
		foreach ($whitelist as $app) {
			$newWhitelist[] = trim($app);
		}

		$this->appWhitelist->setAppWhitelist($newWhitelist);
		$this->appWhitelist->enableWhitelist(json_decode($useWhitelist));
		$this->handler->setGuestsDisplayName($group);

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
		return [
			'isGuest' => false,
			'enabled' => $this->appWhitelist->isWhitelistEnabled(),
			'apps' => $this->appWhitelist->getWhitelist()
		];
	}

	/**
	 * AJAX handler for resetting the whitelisted apps
	 *
	 * @NoAdminRequired
	 * @return DataResponse with the reset whitelist
	 */
	public function resetWhitelist() {
		$this->appWhitelist->resetAppWhitelist();
		return new DataResponse([
			'whitelist' => $this->appWhitelist->getAppWhitelist()
		]);
	}
}
