<?php
/**
 * ownCloud
 *
 * @author Artur Neumann <artur@jankaritech.com>
 * @copyright Copyright (c) 2018 Artur Neumann artur@jankaritech.com
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

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Page\SetPasswordPage;
use PHPUnit\Framework\Assert;
use Page\FilesPage;

require_once 'bootstrap.php';

/**
 * WebUI Guests context.
 */
class WebUIGuestsContext extends RawMinkContext implements Context {
	/**
	 *
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 *
	 * @var WebUIGeneralContext
	 */
	private $webUIGeneralContext;

	/**
	 *
	 * @var GuestsContext
	 */
	private $guestsContext;

	/**
	 *
	 * @var EmailContext
	 */
	private $emailContext;

	/**
	 *
	 * @var SetPasswordPage
	 */
	private $setPasswordPage;

	/**
	 *
	 * @var string
	 */
	private $userAddDialogBoxFramework = "Add %s (guest) Guest";

	/**
	 *
	 * @var FilesPage
	 */
	private $filesPage;

	/**
	 * WebUIGuestsContext constructor.
	 *
	 * @param SetPasswordPage $setPasswordPage
	 * @param FilesPage $filesPage
	 */
	public function __construct(SetPasswordPage $setPasswordPage, FilesPage $filesPage) {
		$this->setPasswordPage = $setPasswordPage;
		$this->filesPage = $filesPage;
	}

	/**
	 * @return string|null
	 */
	public function getGuestGroupName() {
		$configkeyList = $this->featureContext->getConfigKeyList('guests');
		foreach ($configkeyList as $config) {
			if ($config['configkey'] === 'group') {
				return $config['value'];
			}
		}
		return null;
	}

	/**
	 * @Given guest user :user has been created with email :email and password :password
	 *
	 * @param string $user
	 * @param string $email
	 * @param string $password
	 *
	 * @return void
	 * @throws Exception
	 */
	public function guestUserHasBeenCreatedWithEmailAndPassword($user, $email, $password) {
		$this->featureContext->createUser($user, $password, $user, $email);
		$this->featureContext->addUserToGroup($user, $this->getGuestGroupName());
	}

	/**
	 * @When guest user :user registers with email :guestEmail and sets password to :password using the webUI
	 *
	 * @param string $guestDisplayName
	 * @param string $guestEmail
	 * @param string $password
	 *
	 * @return void
	 */
	public function guestUserRegistersWithEmailAndSetsPasswordToUsingTheWebui($guestDisplayName, $guestEmail, $password) {
		$userName = $this->guestsContext->getCreatedGuests()[$guestDisplayName];
		$fullRegisterUrl = $this->guestsContext->getRegistrationUrl($userName);
		$session = $this->getSession();

		$this->setPasswordPage->setPagePath($fullRegisterUrl);
		$this->setPasswordPage->open();
		$this->setPasswordPage->waitTillPageIsLoaded($session);
		$this->setPasswordPage->setTheEmail($guestEmail);
		$this->setPasswordPage->setThePassword($password, $session);
		$this->featureContext->rememberUserPassword($userName, $password);
	}

	/**
	 * @When guest user :user registers and sets password to :password using the webUI
	 *
	 * @param string $guestDisplayName
	 * @param string $password
	 *
	 * @return void
	 */
	public function guestUserRegistersUsingWebUI($guestDisplayName, $password) {
		$userName = $this->guestsContext->prepareUserNameAsFrontend(
			$this->guestsContext->getCreatedGuests()[$guestDisplayName]
		);

		$fullRegisterUrl = $this->guestsContext->getRegistrationUrl($userName);
		$session = $this->getSession();
		$this->setPasswordPage->setPagePath($fullRegisterUrl);
		$this->setPasswordPage->open();
		$this->setPasswordPage->waitTillPageIsLoaded($session);
		$this->setPasswordPage->setThePassword($password, $session);
		$this->featureContext->rememberUserPassword($userName, $password);
	}

	/**
	 * @When the user shares file :fileName with guest user with email :email using webUI
	 *
	 * @param string $fileName
	 * @param string $email
	 *
	 * @return void
	 */
	public function theUserSharesFileWithGuestUserWithEmailUsingWebui($fileName, $email) {
		$this->filesPage->openSharingDialog($fileName, $this->getSession());
		$sharingDialog = $this->filesPage->getSharingDialog();
		$userAddDialog = \sprintf($this->userAddDialogBoxFramework, $email);
		$sharingDialog->shareWithUserOrGroup(
			$email, $userAddDialog, $this->getSession()
		);
		$this->featureContext->addUserToCreatedUsersList($email, null);
		$this->guestsContext->addToCreatedGuestsList($email, $email);
	}

	/**
	 * @Then user :user should not be displayed in dropdown as guest user
	 *
	 * @param string $user
	 *
	 * @return void
	 */
	public function userShouldNotBeDisplayedInTheDropdownAsGuestUser($user) {
		$sharingDialog = $this->filesPage->getSharingDialog();
		$arrayList = $sharingDialog->getAutoCompleteItemsList();
		$userAddDialog = \sprintf($this->userAddDialogBoxFramework, $user);
		PHPUnit\Framework\Assert::assertNotContains($userAddDialog, $arrayList);
	}

	/**
	 * @Then a warning should be displayed on the set-password-page saying :expectedMessage
	 *
	 * @param string $expectedMessage
	 *
	 * @return void
	 */
	public function assertWarningMessage($expectedMessage) {
		foreach ($this->setPasswordPage->getWarningMessages() as $message) {
			if ($message->getText() === $expectedMessage) {
				return true;
			}
		}
		Assert::fail(
			"could not find message with the text '$expectedMessage'"
		);
	}

	/**
	 * This will run before EVERY scenario.
	 * It will set the properties for this object.
	 *
	 * @BeforeScenario @webUI
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 */
	public function before(BeforeScenarioScope $scope) {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->emailContext = $environment->getContext('EmailContext');
		$this->featureContext = $environment->getContext('FeatureContext');
		$this->webUIGeneralContext = $environment->getContext('WebUIGeneralContext');
		$this->guestsContext = $environment->getContext('GuestsContext');
	}
}
