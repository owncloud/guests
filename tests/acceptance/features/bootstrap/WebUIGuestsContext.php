<?php declare(strict_types=1);
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
	private $userAddDialogBoxFramework = "Add %s Guest";

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
	 * @When guest user :user registers with email :guestEmail and sets password to :password using the webUI
	 *
	 * @param string $guestDisplayName
	 * @param string $guestEmail
	 * @param string $password
	 *
	 * @return void
	 * @throws Exception
	 */
	public function guestUserRegistersWithEmailAndSetsPasswordToUsingTheWebUI(
		string $guestDisplayName,
		string $guestEmail,
		string $password
	): void {
		$userName = $this->guestsContext->getCreatedGuests()[$guestDisplayName];
		$fullRegisterUrl = $this->guestsContext->getRegistrationUrl($userName);
		$session = $this->getSession();

		$this->setPasswordPage->setPagePath($fullRegisterUrl);
		$this->setPasswordPage->open();
		$this->setPasswordPage->waitTillPageIsLoaded($session);
		$this->setPasswordPage->setTheEmail($guestEmail);
		$this->setPasswordPage->setThePassword($password);
		$this->featureContext->rememberUserPassword($userName, $password);
	}

	/**
	 * @When guest user :user registers and sets password to :password using the webUI
	 *
	 * @param string $guestDisplayName
	 * @param string $password
	 *
	 * @return void
	 * @throws Exception
	 */
	public function guestUserRegistersUsingWebUI(
		string $guestDisplayName,
		string $password
	): void {
		$userName = $this->guestsContext->prepareUserNameAsFrontend(
			$this->guestsContext->getCreatedGuests()[$guestDisplayName]
		);

		$fullRegisterUrl = $this->guestsContext->getRegistrationUrl($userName);
		$session = $this->getSession();
		$this->setPasswordPage->setPagePath($fullRegisterUrl);
		$this->setPasswordPage->open();
		$this->setPasswordPage->waitTillPageIsLoaded($session);
		$this->setPasswordPage->setThePassword($password);
		$this->featureContext->rememberUserPassword($userName, $password);
	}

	/**
	 * @When the user shares file/folder :fileName with guest user with email :email using the webUI
	 *
	 * @param string $fileName
	 * @param string $email
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function theUserSharesFileWithGuestUserWithEmailUsingWebUI(
		string $fileName,
		string $email
	): void {
		$this->filesPage->openSharingDialog($fileName, $this->getSession());
		$sharingDialog = $this->filesPage->getSharingDialog();
		$userAddDialog = \sprintf($this->userAddDialogBoxFramework, $email);
		$sharingDialog->shareWithUserOrGroup(
			$email,
			$userAddDialog,
			$this->getSession(),
			false
		);
		$this->featureContext->addUserToCreatedUsersList($email, null);
		$this->guestsContext->addToCreatedGuestsList($email, $email);
	}

	/**
	 * @Then user :user should not be displayed in the dropdown as a guest user
	 *
	 * @param string $user
	 *
	 * @return void
	 */
	public function userShouldNotBeDisplayedInTheDropdownAsGuestUser(string $user): void {
		$sharingDialog = $this->filesPage->getSharingDialog();
		$arrayList = $sharingDialog->getAutoCompleteItemsList();
		$userAddDialog = \sprintf($this->userAddDialogBoxFramework, $user);
		Assert::assertNotContains(
			$userAddDialog,
			$arrayList,
			__METHOD__ . " user $user was displayed in the dropdown as a guest user when it should not be"
		);
	}

	/**
	 * @Then a warning should be displayed on the set-password-page saying :expectedMessage
	 *
	 * @param string $expectedMessage
	 *
	 * @return void
	 */
	public function assertWarningMessage(string $expectedMessage): void {
		foreach ($this->setPasswordPage->getWarningMessages() as $message) {
			if ($message->getText() === $expectedMessage) {
				return;
			}
		}
		Assert::fail(
			"could not find message with the text '$expectedMessage' on the set-password-page"
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
	public function before(BeforeScenarioScope $scope): void {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->emailContext = $environment->getContext('EmailContext');
		$this->featureContext = $environment->getContext('FeatureContext');
		$this->webUIGeneralContext = $environment->getContext('WebUIGeneralContext');
		$this->guestsContext = $environment->getContext('GuestsContext');
	}
}
