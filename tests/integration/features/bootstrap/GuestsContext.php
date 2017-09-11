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
use GuzzleHttp\Client;

require __DIR__ . '/../../vendor/autoload.php';


/**
 * Guests context.
 */
class GuestsContext implements Context, SnippetAcceptingContext {
	use Webdav;

	/** @var array */
	private $createdGuests = [];

	public function prepareUserNameAsFrontend($guestDisplayName, $guestEmail) {
		return strtolower(trim(urldecode($guestEmail)));
	}

	/**
	 * @Given user :user creates guest user :guestDisplayName with email :guestEmail
	 * @param string $user
	 * @param string $guestDisplayName
	 * @param string $guestEmail
	 */
	public function userCreatedAGuestUser($user, $guestDisplayName, $guestEmail) {
		$fullUrl = substr($this->baseUrl, 0, -4) . '/index.php/apps/guests/users';
		//Replicating frontend behaviour
		$userName = $this->prepareUserNameAsFrontend($guestDisplayName, $guestEmail);
		$fullUrl = $fullUrl . '?displayName=' . $guestDisplayName . '&email=' . $guestEmail . '&username=' . $userName;
		$client = new Client();
		$options = [];
		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}
		$request = $client->createRequest("PUT", $fullUrl, $options);
		$request->addHeader('Content-Type', 'application/x-www-form-urlencoded');

		try {
			$this->response = $client->send($request);
		} catch (\GuzzleHttp\Exception\BadResponseException $e) {
			// 4xx and 5xx responses cause an exception
			$this->response = $e->getResponse();
		}
		$this->createdGuests[$guestDisplayName] = $guestEmail;
	}

	/**
	 * @Then check that user :user is a guest
	 * @param string $guestDisplayName
	 */
	public function checkGuestUser($guestDisplayName) {
		$userName = $this->prepareUserNameAsFrontend($guestDisplayName, $this->createdGuests[$guestDisplayName]);
		$this->checkThatUserBelongsToGroup($userName, 'guest_app');
	}

	/**
	 * @Then guest user :user is deleted
	 * @param string $guestDisplayName
	 */
	public function deleteGuestUser($guestDisplayName) {
		$userName = $this->prepareUserNameAsFrontend($guestDisplayName, $this->createdGuests[$guestDisplayName]);
		$this->deleteUser($userName);
	}

	/*Processes the body of an email sent and gets the register url
	  It depends on the content of the email*/
	public function extractRegisterUrl($emailBody) {
		$knownString = 'Activate your guest account at ownCloud by setting a password: ';
		$nextString = 'Then view it';
		$posKnownString = strpos($emailBody, $knownString);
		$posNextString = strpos($emailBody, $nextString, $posKnownString + strlen($knownString));
		$urlRegister = substr($emailBody,
								 $posKnownString + strlen($knownString),
								 $posNextString - ($posKnownString + strlen($knownString)));
		$urlRegister = preg_replace('/[\s]+/mu', ' ', $urlRegister);
		$urlRegister = str_replace('=', '', $urlRegister);
		$urlRegister = str_replace(' ', '', $urlRegister);
		return $urlRegister;
	}

	/**
	 * @Given guest user :user registers
	 * @param string $guestDisplayName
	 */
	public function guestUserRegisters($guestDisplayName) {
		$userName = $this->prepareUserNameAsFrontend($guestDisplayName, $this->createdGuests[$guestDisplayName]);
		$emails = $this->getEmails();
		$lastEmailBody = $emails->items[0]->Content->Body;
		$fullRegisterUrl = $this->extractRegisterUrl($lastEmailBody);
		
		$exploded = explode('/', $fullRegisterUrl);
		$email = $exploded[7];
		$token = $exploded[8];		
		$registerUrl = implode('/', array_splice($exploded, 0, 7));
		
		$client = new Client();
		$options['body'] = [
							'email' => $email,
							'token' => $token,
							'password' => $this->regularUser
							];
		try {
			$this->response = $client->send($client->createRequest('POST', $registerUrl, $options));
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
		}
	}

	/**
	 * @BeforeScenario
	 * @AfterScenario
	 */
	public function cleanupGuests()
	{
		foreach($this->createdGuests as $displayName => $email) {
			$this->deleteGuestUser($displayName);
		}
	}

}


