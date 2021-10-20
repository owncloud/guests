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
use PHPUnit\Framework\Assert;
use TestHelpers\EmailHelper;
use TestHelpers\HttpRequestHelper;
use TestHelpers\SetupHelper;
use TestHelpers\UploadHelper;

require_once 'bootstrap.php';

/**
 * Guests context.
 */
class GuestsContext implements Context, SnippetAcceptingContext {
	/**
	 * Stores the email of each created guest, keyed by guest display name.
	 *
	 * @var array
	 */
	private $createdGuests = [];

	/**
	 *
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 *
	 * @var EmailContext
	 */
	private $emailContext;

	/**
	 * @return string
	 */
	private function getRelativePathToTestDataFolder(): string {
		$relativePath
			= $this->featureContext->getPathFromCoreToAppAcceptanceTests(__DIR__);
		return "$relativePath/data/";
	}

	/**
	 * @return array
	 */
	public function getCreatedGuests(): array {
		return $this->createdGuests;
	}

	/**
	 *
	 * @param string $guestDisplayName
	 * @param string $guestEmail
	 *
	 * @return void
	 */
	public function addToCreatedGuestsList(
		string $guestDisplayName,
		string $guestEmail
	): void {
		$this->createdGuests[$guestDisplayName] = $guestEmail;
	}

	/**
	 * disable CSRF
	 *
	 * @throws Exception
	 * @return string the previous setting of csrf.disabled
	 */
	private function disableCSRFFromGuestsScenario(): string {
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
	private function setCSRFDotDisabledFromGuestsScenario(string $setting): string {
		$oldCSRFSetting = SetupHelper::runOcc(
			['config:system:get', 'csrf.disabled'],
			$this->featureContext->getStepLineRef()
		)['stdOut'];

		if ($setting === "") {
			SetupHelper::runOcc(
				['config:system:delete', 'csrf.disabled'],
				$this->featureContext->getStepLineRef()
			);
		} elseif ($setting !== null) {
			SetupHelper::runOcc(
				[
					'config:system:set',
					'csrf.disabled',
					'--type',
					'boolean',
					'--value',
					$setting
				],
				$this->featureContext->getStepLineRef()
			);
		}
		return \trim($oldCSRFSetting);
	}

	/**
	 * @param string $guestEmail
	 *
	 * @return string
	 */
	public function prepareUserNameAsFrontend(string $guestEmail): string {
		return \str_replace('+', '%2B', \strtolower(\trim($guestEmail)));
	}

	/**
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 */
	public function userUploadsFileFromGuestsDataFolder(
		string $user,
		string $source,
		string $destination
	): void {
		$source = $this->getRelativePathToTestDataFolder() . $source;
		$this->featureContext->userUploadsAFileTo($user, $source, $destination);
	}

	/**
	 * @Given user :user has uploaded file :source from the guests test data folder to :destination
	 *
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 */
	public function userHasUploadedFileFromGuestsDataFolderTo(
		string $user,
		string $source,
		string $destination
	): void {
		$this->userUploadsFileFromGuestsDataFolder(
			$user,
			$source,
			$destination
		);
		$this->featureContext->theHTTPStatusCodeShouldBeSuccess();
	}

	/**
	 * @When user :user uploads file :source from the guests test data folder to :destination using the WebDAV API
	 *
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 */
	public function userUploadsFileFromGuestsDataFolderTo(
		string $user,
		string $source,
		string $destination
	): void {
		$this->userUploadsFileFromGuestsDataFolder(
			$user,
			$source,
			$destination
		);
	}

	/**
	 * Uploading with old/new dav and chunked/non-chunked.
	 *
	 * @When user :user uploads overwriting file :source from the guests test data folder to :destination with all mechanisms using the WebDAV API
	 *
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 * @throws Exception
	 */
	public function userUploadsAFileToWithAllMechanisms(
		string $user,
		string $source,
		string $destination
	): void {
		$source = $this->getRelativePathToTestDataFolder() . $source;
		$uploadResponses = UploadHelper::uploadWithAllMechanisms(
			$this->featureContext->getBaseUrl(),
			$this->featureContext->getActualUsername($user),
			$this->featureContext->getUserPassword($user),
			$source,
			$destination,
			$this->featureContext->getStepLineRef(),
			true
		);
		$this->featureContext->setUploadResponses($uploadResponses);
	}

	/**
	 * @When /^user "([^"]*)" uploads file "([^"]*)" from the guests test data folder to "([^"]*)" in (\d+) chunks (?:with (new|old|v1|v2) chunking and)?\s?using the WebDAV API$/
	 *
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 * @param int $noOfChunks
	 * @param string $chunkingVersion old|v1|new|v2 null for autodetect
	 * @param bool $async use asynchronous move at the end or not
	 *
	 * @return void
	 */
	public function userUploadsAFileToWithChunks(
		string $user,
		string $source,
		string $destination,
		int $noOfChunks = 2,
		?string $chunkingVersion = null,
		bool $async = false
	): void {
		$source = $this->getRelativePathToTestDataFolder() . $source;
		$this->featureContext->userUploadsAFileToWithChunks(
			$user,
			$source,
			$destination,
			$noOfChunks,
			$chunkingVersion,
			$async
		);
	}

	/**
	 * @When /^user "([^"]*)" uploads file "([^"]*)" from the guests test data folder asynchronously to "([^"]*)" in (\d+) chunks using the WebDAV API$/
	 *
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 * @param int  $noOfChunks
	 *
	 * @return void
	 */
	public function userUploadsAFileAsyncToWithChunks(
		string $user,
		string $source,
		string $destination,
		int $noOfChunks = 2
	): void {
		$this->userUploadsAFileToWithChunks(
			$user,
			$source,
			$destination,
			$noOfChunks,
			"new",
			true
		);
	}

	/**
	 * @param string $user
	 * @param string $guestDisplayName
	 * @param string $guestEmail
	 * @param bool $shouldExist
	 *
	 * @return void
	 */
	public function userCreatesAGuestUser(
		string $user,
		string $guestDisplayName,
		string $guestEmail,
		bool $shouldExist
	): void {
		$user = $this->featureContext->getActualUsername($user);
		$fullUrl
			= $this->featureContext->getBaseUrl() . '/index.php/apps/guests/users';
		//Replicating frontend behaviour
		$userName = $this->prepareUserNameAsFrontend($guestEmail);
		$body = [
			'displayName' => $guestDisplayName,
			'userName' => $userName,
			'email' => $guestEmail
		];
		$headers = [];
		$headers['Content-Type'] = 'application/x-www-form-urlencoded';
		$response = HttpRequestHelper::sendRequest(
			$fullUrl,
			$this->featureContext->getStepLineRef(),
			'PUT',
			$user,
			$this->featureContext->getPasswordForUser($user),
			$headers,
			$body
		);

		$this->featureContext->setResponse($response);

		if ($shouldExist) {
			$this->featureContext->theHTTPStatusCodeShouldBeSuccess();
		}

		$this->createdGuests[$guestDisplayName] = $guestEmail;

		// Let core acceptance test functionality know the user that has been
		// created. Core acceptance test AfterScenario will cleanup created users.
		$this->featureContext->addUserToCreatedUsersList(
			$userName,
			$this->featureContext->getPasswordForUser($userName),
			$guestDisplayName,
			$guestEmail,
			$shouldExist
		);
	}

	/**
	 * @Given /^user "([^"]*)" has created guest user "([^"]*)" with email "([^"]*)"$/
	 *
	 * @param string $user
	 * @param string $guestDisplayName
	 * @param string $guestEmail
	 *
	 * @return void
	 */
	public function userCreatesAGuestUserWithEmail(
		string $user,
		string $guestDisplayName,
		string $guestEmail
	): void {
		$this->userCreatesAGuestUser(
			$user,
			$guestDisplayName,
			$guestEmail,
			true
		);
	}

	/**
	 * @When /^user "([^"]*)" (attempts to create|creates) guest user "([^"]*)" with email "([^"]*)" using the API$/
	 *
	 * @param string $user
	 * @param string $attemptTo
	 * @param string $guestDisplayName
	 * @param string $guestEmail
	 *
	 * @return void
	 */
	public function userHasCreatedAGuestUserWithEmail(
		string $user,
		string $attemptTo,
		string $guestDisplayName,
		string $guestEmail
	): void {
		$shouldExist
			= ($attemptTo == "creates");
		$this->userCreatesAGuestUser(
			$user,
			$guestDisplayName,
			$guestEmail,
			$shouldExist
		);
	}

	/**
	 * @Given /^the administrator has created guest user "([^"]*)" with email "([^"]*)"$/
	 *
	 * @param string $guestDisplayName
	 * @param string $guestEmail
	 *
	 * @return void
	 */
	public function theAdministratorHasCreatedAGuestUser(
		string $guestDisplayName,
		string $guestEmail
	): void {
		$this->userCreatesAGuestUser(
			$this->featureContext->getAdminUsername(),
			$guestDisplayName,
			$guestEmail,
			true
		);
	}

	/**
	 * @When /^the administrator (attempts to create|creates) guest user "([^"]*)" with email "([^"]*)" using the API$/
	 *
	 * @param string $attemptTo
	 * @param string $guestDisplayName
	 * @param string $guestEmail
	 *
	 * @return void
	 */
	public function theAdministratorCreatesAGuestUser(
		string $attemptTo,
		string $guestDisplayName,
		string $guestEmail
	): void {
		$shouldExist
			= ($attemptTo == "creates");
		$this->userCreatesAGuestUser(
			$this->featureContext->getAdminUsername(),
			$guestDisplayName,
			$guestEmail,
			$shouldExist
		);
	}

	/**
	 * @Then user :user should be a guest user
	 *
	 * @param string $guestDisplayName
	 *
	 * @return void
	 */
	public function checkGuestUser(string $guestDisplayName): void {
		Assert::assertArrayHasKey(
			$guestDisplayName,
			$this->createdGuests,
			__METHOD__ . " guest user '$guestDisplayName' has not been successfully created by this scenario"
		);
		$userName = $this->prepareUserNameAsFrontend(
			$this->createdGuests[$guestDisplayName]
		);
		$this->featureContext->userShouldBelongToGroup($userName, 'guest_app');
	}

	/**
	 * @Given guest user :user has been deleted
	 *
	 * @param string $guestDisplayName
	 *
	 * @return void
	 * @throws Exception
	 */
	public function deleteGuestUser(string $guestDisplayName): void {
		$userName = $this->prepareUserNameAsFrontend(
			$this->createdGuests[$guestDisplayName]
		);
		$this->featureContext->deleteUser($userName);
	}

	/**
	 * Process the body of an email and get the URL for guest registration.
	 * The guest registration URL looks something like:
	 * http://owncloud/apps/guests/register/guest@example.com/bxuPw8ixQvxR5EvfAMEFG
	 *
	 * @param string $emailBody
	 *
	 * @return string URL for the guest to register
	 */
	public function extractRegisterUrl(string $emailBody): string {
		// The character sequence "=\r\n" encodes soft line breaks in the plain
		// text email. Remove these so that we get the full strings that we are
		// searching for without them being "randomly" split by the soft line
		// breaks.
		// https://en.wikipedia.org/wiki/Quoted-printable
		$emailBody = \str_replace("=\r\n", "", $emailBody);
		$knownString
			= 'Activate your guest account at ownCloud by setting a password: ';
		$nextString = 'Then view it';
		$posKnownString = \strpos($emailBody, $knownString);
		$posNextString = \strpos(
			$emailBody,
			$nextString,
			$posKnownString + \strlen($knownString)
		);
		$urlRegister = \substr(
			$emailBody,
			$posKnownString + \strlen($knownString),
			$posNextString - ($posKnownString + \strlen($knownString))
		);
		$urlRegister = \preg_replace('/[\s]+/mu', ' ', $urlRegister);
		$urlRegister = \str_replace('=', '', $urlRegister);
		$urlRegister = \str_replace(' ', '', $urlRegister);
		return $urlRegister;
	}

	/**
	 * @param string $address
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getRegistrationUrl(string $address): string {
		$lastEmailBody = EmailHelper::getBodyOfLastEmail(
			$this->emailContext->getLocalMailhogUrl(),
			$address,
			$this->featureContext->getStepLineRef()
		);
		return $this->extractRegisterUrl($lastEmailBody);
	}

	/**
	 * @param string $guestDisplayName
	 * @param string $password
	 *
	 * @return void
	 * @throws Exception
	 */
	public function registerGuestUser(
		string $guestDisplayName,
		?string $password = null
	): void {
		$oldCSRFSetting = $this->disableCSRFFromGuestsScenario();
		$userName = $this->createdGuests[$guestDisplayName];
		$fullRegisterUrl = $this->getRegistrationUrl($userName);
		$explodedFullRegisterUrl = \explode('/', $fullRegisterUrl);
		$sizeOfExplodedFullRegisterUrl = \count($explodedFullRegisterUrl);

		// The email address is the 2nd-last part of the URL
		$email = $explodedFullRegisterUrl[$sizeOfExplodedFullRegisterUrl - 2];
		// The token is the last part of the URL
		$token = $explodedFullRegisterUrl[$sizeOfExplodedFullRegisterUrl - 1];
		$registerUrl = \implode(
			'/',
			\array_splice($explodedFullRegisterUrl, 0, $sizeOfExplodedFullRegisterUrl - 2)
		);

		if ($password === null) {
			$password = $this->featureContext->getPasswordForUser($userName);
		} else {
			$password = (string) $this->featureContext->getActualPassword(
				$password
			);
		}

		$headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
		$body = [
			'email' => $email,
			'token' => $token,
			'password' => $password
		];

		$response = HttpRequestHelper::sendRequest(
			$registerUrl,
			$this->featureContext->getStepLineRef(),
			'POST',
			null,
			null,
			$headers,
			$body
		);

		$this->featureContext->setResponse($response);
		$this->featureContext->rememberUserPassword($userName, $password);
		$this->setCSRFDotDisabledFromGuestsScenario($oldCSRFSetting);
	}

	/**
	 * @Given guest user :user has registered
	 * @Given guest user :user has registered and set password to :password
	 *
	 * @param string $guestDisplayName
	 * @param string $password
	 *
	 * @return void
	 * @throws Exception
	 */
	public function guestUserHasRegistered(
		string $guestDisplayName,
		?string $password = null
	): void {
		$this->registerGuestUser($guestDisplayName, $password);
		$this->featureContext->theHTTPStatusCodeShouldBeSuccess();
	}

	/**
	 * @When guest user :user registers
	 * @When guest user :user registers and sets password to :password
	 *
	 * @param string $guestDisplayName
	 * @param string $password
	 *
	 * @return void
	 * @throws Exception
	 */
	public function guestUserRegisters(
		string $guestDisplayName,
		?string $password = null
	): void {
		$this->registerGuestUser($guestDisplayName, $password);
	}

	/**
	 * @BeforeScenario
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 */
	public function setUpScenario(BeforeScenarioScope $scope): void {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->featureContext = $environment->getContext('FeatureContext');
		$this->emailContext = $environment->getContext('EmailContext');
	}

	/**
	 * @Given /^user "([^"]*)" has shared (?:file|folder|entry) "([^"]*)" with guest user "([^"]*)"(?: with permissions (\d+))?$/
	 *
	 * @param string $sharer
	 * @param string $filePath
	 * @param string $guestUser
	 * @param string|null $permissions
	 *
	 * @return void
	 * @throws Exception
	 */
	public function userHasSharedFolderWithGuestUser(
		string $sharer,
		string $filePath,
		string $guestUser,
		?string $permissions = null
	): void {
		$guestUser = \urldecode($this->prepareUserNameAsFrontend($guestUser));
		$this->featureContext->shareFileWithUserUsingTheSharingApi(
			$sharer,
			$filePath,
			$guestUser,
			$permissions,
			true
		);
		// this is expected to fail if a file is shared with create and delete permissions, which is not possible
		Assert::assertTrue(
			$this->featureContext->isUserOrGroupInSharedData($guestUser, "user", $permissions),
			"User $sharer failed to share $filePath with user $guestUser"
		);
	}
}
