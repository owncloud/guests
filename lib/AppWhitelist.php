<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
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
namespace OCA\Guests;


use OCP\IConfig;

/**
 * Only allow whitelisted apps to be accessed by guests
 *
 * @package OCA\Guests
 */
class AppWhitelist {

	const CORE_WHITELIST = 'core,files,guests';
	const DEFAULT_WHITELIST = 'settings,avatar,files_external,files_trashbin,files_versions,files_sharing,files_texteditor,activity,firstrunwizard,gallery,notifications';

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * AppWhitelist constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(
		IConfig $config
	) {
		$this->config = $config;
	}

	/**
	 * @var AppWhitelist
	 */
	private static $instance;

	/**
	 * @deprecated use DI
	 * @return AppWhitelist
	 */
	public static function createForStaticLegacyCode() {
		if (!self::$instance) {
			self::$instance = new AppWhitelist (
				\OC::$server->getConfig()
			);

		}
		return self::$instance;
	}

	/**
	 * Checks is whitelisting to specific apps is enabled
	 *
	 * @return bool
	 */
	public function isWhitelistEnabled() {
		return $this->config->getAppValue('guests', 'usewhitelist', 'true') === 'true';
	}

	/**
	 * Enables/disables whitelisting to specific apps
	 *
	 * @param bool $enable
	 */
	public function enableWhitelist($enable) {
		if ($enable == true) {
			$this->config->setAppValue('guests', 'usewhitelist', 'true');
		} else {
			$this->config->setAppValue('guests', 'usewhitelist', 'false');
		}
	}

	/**
	 * Sets guests whitelisting to specific apps in $whitelist array
	 *
	 * @param string[] $whitelist
	 */
	public function setAppWhitelist($whitelist) {
		$newWhitelist = join(',', $whitelist);
		$this->config->setAppValue('guests', 'whitelist', $newWhitelist);
	}

	/**
	 * Resets guests whitelisting to default
	 */
	public function resetAppWhitelist() {
		$this->config->setAppValue('guests', 'whitelist', self::DEFAULT_WHITELIST);
	}

	/**
	 * Get guests whitelist
	 *
	 * @return string[]
	 */
	public function getAppWhitelist() {
		$whitelist = $this->config->getAppValue(
			'guests',
			'whitelist',
			self::DEFAULT_WHITELIST
		);

		return explode(',' , $whitelist);
	}

	/**
	 * Get core and guests whitelist as a single string
	 *
	 * @return string[]
	 */
	public function getWhitelist() {
		$whitelist = self::CORE_WHITELIST;
		$whitelist .=  ',' . $this->config->getAppValue(
			'guests',
			'whitelist',
			self::DEFAULT_WHITELIST
		);

		return explode(',' , $whitelist);
	}
}
