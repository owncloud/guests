<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
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


use OCA\Guests\Mail;
use OC\AppFramework\Http;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;

class UsersController extends Controller {
	/**
	 * @var IRequest
	 */
	protected $request;
	/**
	 * @var IUserManager
	 */
	private $userManager;
	/**
	 * @var IL10N
	 */
	private $l10n;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IMailer
	 */
	private $mailer;
	/**
	 * @var Mail
	 */
	private $mail;
	/**
	 * @var IGroupManager
	 */
	private $groupManager;
	/**
	 * @var ISecureRandom
	 */
	private $secureRandom;


	/**
	 * UsersController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param IMailer $mailer
	 * @param Mail $mail
	 * @param ISecureRandom $secureRandom
	 */
	public function __construct($appName,
								IRequest $request,
								IUserManager $userManager,
								IGroupManager $groupManager,
								IL10N $l10n,
								IConfig $config,
								IMailer $mailer,
								Mail $mail,
								ISecureRandom $secureRandom
	) {
		parent::__construct($appName, $request);

		$this->request = $request;
		$this->userManager = $userManager;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->mailer = $mailer;
		$this->mail = $mail;
		$this->groupManager = $groupManager;
		$this->secureRandom = $secureRandom;
	}

	/**
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param $username
	 * @param $email
	 * @param $displayName
	 * @return DataResponse
	 */
	public function create($email, $displayName) {
		$errorMessages = [];
		$email = trim(urldecode($email));
		$username = strtolower($email);

		if (empty($email) || !$this->mailer->validateMailAddress($email)) {
			$errorMessages['email'] = (string)$this->l10n->t(
				'Invalid mail address'
			);
		}

		if ($this->userManager->userExists($username)) {
			$errorMessages['email'] = (string)$this->l10n->t(
				'A username with that email already exists.'
			);
		}

		if (!empty($errorMessages)) {
			return new DataResponse(
				[
					'errorMessages' => $errorMessages
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

        // HACK: make a password that is compliant with potential password policies
        $pw = '';
        for ($i = 0; $i < 20; $i++) {
            $pw .= 'aA1' . \OC::$server->getConfig()->getAppValue('password_policy', 'spv_def_special_chars_value', '');
        }

		$user = $this->userManager->createUser(
			$username,
			$this->secureRandom->generate(20) . $pw
		);

		$user->setEMailAddress($email);

		if (!empty($displayName)) {
			$user->setDisplayName($displayName);
		}

		$token = $this->secureRandom->getMediumStrengthGenerator()->generate(
			21,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		);

		$this->config->setUserValue(
			$username,
			'guests',
			'registerToken',
			$token
		);

		$this->config->setUserValue(
			$username,
			'guests',
			'created',
			time()
		);

		$this->config->setUserValue(
			$username,
			'owncloud',
			'isGuest',
			'1'
		);

		return new DataResponse(
			[
				'message' => (string)$this->l10n->t(
					'User successfully created'
				)
			],
			Http::STATUS_CREATED
		);
	}

}
