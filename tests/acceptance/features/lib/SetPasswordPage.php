<?php
/**
 * ownCloud
 *
 * @author Artur Neumann <artur@jankaritech.com>
 * @copyright Copyright (c) 2017 Artur Neumann artur@jankaritech.com
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License,
 * as published by the Free Software Foundation;
 * either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Page;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Session;
use Behat\Mink\Element\NodeElement;

/**
 * page where the guest user can set its password
 */
class SetPasswordPage extends OwncloudPage {

	/**
	 * @var string $path
	 */
	protected $path = '/';
	protected $passwordInputId = "password";
	protected $emailInputId = "email";
	protected $submitLoginId = "submit";
	protected $warningMessagesXpath = "//div[@class='warning']";

	/**
	 *
	 * @param string $newPassword
	 *
	 * @return void
	 * @throws ElementNotFoundException
	 */
	public function setThePassword(string $newPassword): void {
		$this->fillField($this->passwordInputId, $newPassword);
		$submitButton = $this->findById($this->submitLoginId);
		$this->assertElementNotNull(
			$submitButton,
			__METHOD__ . " id: $this->submitLoginId could not find submit button"
		);
		$submitButton->click();
	}

	/**
	 *
	 * @param string $newEmail
	 *
	 * @return void
	 * @throws ElementNotFoundException
	 */
	public function setTheEmail(string $newEmail): void {
		$this->fillField($this->emailInputId, $newEmail);
	}
	/**
	 *
	 * @return NodeElement[]
	 */
	public function getWarningMessages(): array {
		return $this->findAll("xpath", $this->warningMessagesXpath);
	}

	/**
	 * there is no reliable loading indicator on the login page, so just wait for
	 * the password field to be there.
	 *
	 * @param Session $session
	 * @param int $timeout_msec
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function waitTillPageIsLoaded(
		Session $session,
		int $timeout_msec = STANDARD_UI_WAIT_TIMEOUT_MILLISEC
	): void {
		$currentTime = \microtime(true);
		$end = $currentTime + ($timeout_msec / 1000);
		while ($currentTime <= $end) {
			if (($this->findById($this->passwordInputId) !== null)
			) {
				break;
			}
			\usleep(STANDARD_SLEEP_TIME_MICROSEC);
			$currentTime = \microtime(true);
		}

		if ($currentTime > $end) {
			throw new \Exception(
				__METHOD__ . " timeout waiting for page to load"
			);
		}

		$this->waitForOutstandingAjaxCalls($session);
	}
}
