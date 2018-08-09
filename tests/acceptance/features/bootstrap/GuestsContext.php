<?php
/**
 * @author Sergio Bertolin <sbertolin@solidgear.es>
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

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use GuzzleHttp\Client;
use TestHelpers\EmailHelper;
use TestHelpers\SetupHelper;

require_once 'bootstrap.php';

/**
 * Guests context.
 */
class GuestsContext implements Context, SnippetAcceptingContext {
	use BasicStructure;

	/**
	 * Stores the email of each created guest, keyed by guest display name.
	 *
	 * @var array
	 */
	private $createdGuests = [];

	/**
	 *
	 * @var EmailContext
	 */
	private $emailContext;

	/**
	 * The relative path from the core tests/acceptance folder to the test data folder.
	 *
	 * @var string
	 */
	private $relativePathToTestDataFolder = '../../apps/guests/tests/acceptance/data/';

	/**
	 * disable CSRF
	 *
	 * @throws Exception
	 * @return string the previous setting of csrf.disabled
	 */
	private function disableCSRFFromGuestsScenario() {
		return $this->setCSRFDotDisabledFromGuestsScenario('true');
	}

	/**
	 * set csrf.disabled
	 *
	 * @param string $setting "true", "false" or "" to delete the setting
	 *
	 * @throws Exception
	 * @return string the previous setting of csrf.disabled
	 */
	private function setCSRFDotDisabledFromGuestsScenario($setting) {
		$oldCSRFSetting = SetupHelper::runOcc(
			['config:system:get', 'csrf.disabled']
		)['stdOut'];

		if ($setting === "") {
			SetupHelper::runOcc(['config:system:delete', 'csrf.disabled']);
		} elseif ($setting !== null) {
			SetupHelper::runOcc(
				[
					'config:system:set',
					'csrf.disabled',
					'--type',
					'boolean',
					'--value',
					$setting
				]
			);
		}
		return \trim($oldCSRFSetting);
	}

	public function prepareUserNameAsFrontend($guestEmail) {
		return \strtolower(\trim(\urldecode($guestEmail)));
	}

	/**
	 * @When user :user uploads file :source from the guests test data folder to :destination using the WebDAV API
	 * @Given user :user has uploaded file :source from the guests test data folder to :destination
	 *
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 */
	public function userUploadsFileFromGuestsDataFolderTo($user, $source, $destination) {
		$source = $this->relativePathToTestDataFolder . $source;
		$this->userUploadsAFileTo($user, $source, $destination);
	}

	/**
	 * @When /^user "([^"]*)" (attempts to create|creates) guest user "([^"]*)" with email "([^"]*)" using the API$/
	 * @Given /^user "([^"]*)" has (attempted to create|created) guest user "([^"]*)" with email "([^"]*)"$/
	 * @param string $user
	 * @param string $attemptTo
	 * @param string $guestDisplayName
	 * @param string $guestEmail
	 */
	public function userCreatesAGuestUser($user, $attemptTo, $guestDisplayName, $guestEmail) {
		$shouldHaveBeenCreated = (($attemptTo == "creates") || ($attemptTo === "created"));
		$fullUrl = $this->getBaseUrl() . '/index.php/apps/guests/users';
		//Replicating frontend behaviour
		$userName = $this->prepareUserNameAsFrontend($guestEmail);
		$fullUrl = $fullUrl . '?displayName=' . $guestDisplayName . '&email=' . $guestEmail . '&username=' . $userName;
		$client = new Client();
		$options = [];
		$options['auth'] = $this->getAuthOptionForUser($user);
		$request = $client->createRequest("PUT", $fullUrl, $options);
		$request->addHeader('Content-Type', 'application/x-www-form-urlencoded');

		try {
			$this->response = $client->send($request);
		} catch (\GuzzleHttp\Exception\BadResponseException $e) {
			// 4xx and 5xx responses cause an exception
			$this->response = $e->getResponse();
		}
		$this->createdGuests[$guestDisplayName] = $guestEmail;

		// Let core acceptance test functionality know the user that has been created.
		// Core acceptance test AfterScenario will cleanup created users.
		$this->addUserToCreatedUsersList(
			$userName,
			$this->getPasswordForUser($userName),
			$guestDisplayName,
			$guestEmail,
			$shouldHaveBeenCreated);
	}

	/**
	 * @Then user :user should be a guest user
	 * @param string $guestDisplayName
	 */
	public function checkGuestUser($guestDisplayName) {
		$userName = $this->prepareUserNameAsFrontend($this->createdGuests[$guestDisplayName]);
		$this->userShouldBelongToGroup($userName, 'guest_app');
	}

	/**
	 * @Given guest user :user has been deleted
	 * @param string $guestDisplayName
	 */
	public function deleteGuestUser($guestDisplayName) {
		$userName = $this->prepareUserNameAsFrontend($this->createdGuests[$guestDisplayName]);
		$this->deleteUser($userName);
	}

	/*Processes the body of an email sent and gets the register url
	  It depends on the content of the email*/
	public function extractRegisterUrl($emailBody) {
		$knownString = 'Activate your guest account at ownCloud by setting a password: ';
		$nextString = 'Then view it';
		$posKnownString = \strpos($emailBody, $knownString);
		$posNextString = \strpos($emailBody, $nextString, $posKnownString + \strlen($knownString));
		$urlRegister = \substr($emailBody,
								 $posKnownString + \strlen($knownString),
								 $posNextString - ($posKnownString + \strlen($knownString)));
		$urlRegister = \preg_replace('/[\s]+/mu', ' ', $urlRegister);
		$urlRegister = \str_replace('=', '', $urlRegister);
		$urlRegister = \str_replace(' ', '', $urlRegister);
		return $urlRegister;
	}

	/**
	 * @When guest user :user registers
	 * @Given guest user :user has registered
	 * @param string $guestDisplayName
	 */
	public function guestUserRegisters($guestDisplayName) {
		$oldCSRFSetting = $this->disableCSRFFromGuestsScenario();
		$userName = $this->prepareUserNameAsFrontend($this->createdGuests[$guestDisplayName]);
		$emails = EmailHelper::getEmails($this->emailContext->getMailhogUrl());
		$lastEmailBody = $emails->items[0]->Content->Body;
		$fullRegisterUrl = $this->extractRegisterUrl($lastEmailBody);
		
		$exploded = \explode('/', $fullRegisterUrl);
		$email = $exploded[7];
		$token = $exploded[8];
		$registerUrl = \implode('/', \array_splice($exploded, 0, 7));
		
		$client = new Client();
		$options['body'] = [
							'email' => $email,
							'token' => $token,
							'password' => $this->getPasswordForUser($userName)
							];
		try {
			$this->response = $client->send($client->createRequest('POST', $registerUrl, $options));
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
		}

		$this->setCSRFDotDisabledFromGuestsScenario($oldCSRFSetting);
	}

	/**
	 * @BeforeScenario
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 */
	public function setUpScenario(BeforeScenarioScope $scope) {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->emailContext = $environment->getContext('EmailContext');
	}

	/**
	 * Abstract method implemented from Core's FeatureContext
	 */
	protected function resetAppConfigs() {
		// Remember the current capabilities
		$this->getCapabilitiesCheckResponse();
		$this->savedCapabilitiesXml[$this->getBaseUrl()] = $this->getCapabilitiesXml();
		// Set the required starting values for testing
		$this->setCapabilities($this->getCommonSharingConfigs());
	}
}
