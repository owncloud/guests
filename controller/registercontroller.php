<?php
/**
 * @author Thomas Heinisch <th@bw-tech.de>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Guests\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use OCP\Util;

class RegisterController extends Controller {
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
	 * @var IGroupManager
	 */
	private $groupManager;
	/**
	 * @var ISecureRandom
	 */
	private $secureRandom;
	/**
	 * @var IMailer
	 */
	private $mailer;
	/**
	 *  @var IURLGenerator
	 */
	private $urlGenerator;

	/**
	 * RegisterController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param ISecureRandom $secureRandom
	 * @param IMailer $mailer
	 * @param IUrlGenerator $urlGenerator
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IL10N $l10n,
		IConfig $config,
		ISecureRandom $secureRandom,
		IMailer $mailer,
		IUrlGenerator $urlGenerator
	) {
		parent::__construct($appName, $request);

		$this->request = $request;
		$this->userManager = $userManager;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->secureRandom = $secureRandom;
		$this->mailer = $mailer;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Show the password form
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param string $email
	 * @param string $token
	 *
	 * @return TemplateResponse
	 */
	public function showPasswordForm($email, $token) {
		$errorMessages = [];
		$userId = strtolower($email);

		if (empty($email) || !$this->mailer->validateMailAddress($email)) {
			$errorMessages['email'] = (string)$this->l10n->t(
				'Invalid mail address'
			);
		}

		$isGuest = (bool)$this->config->getUserValue(
			$userId,
			'owncloud',
			'isGuest'
		);

		if (!$isGuest) {
			$errorMessages['username'] = (string)$this->l10n->t(
				'No such guest user'
			);
		}

		$checkToken = $this->config->getUserValue(
			$userId,
			'guests',
			'registerToken'
		);

		if (empty($checkToken)) {
			$errorMessages['token'] = (string)$this->l10n->t(
				'The token is invalid'
			);
		}

		$parameters['email'] = $email;
		$parameters['messages'] = $errorMessages;
		$parameters['token'] = $token;
		$parameters['postAction'] =
			$this->urlGenerator->linkToRouteAbsolute('guests.register.register');

		return new TemplateResponse(
			$this->appName, 'form.password', $parameters, 'guest'
		);

	}

	/**
	 * Perform the registration
	 *
	 * @PublicPage
	 * @NoAdminRequired
	 * @UseSession
	 *
	 * @return TemplateResponse|RedirectResponse
	 */
	public function register() {
		$email = trim($_POST['email']);
		$token = trim($_POST['token']);
		$password = trim($_POST['password']);
		$userId = strtolower($email);
		$parameters = [];

		if (empty($email) || !$this->mailer->validateMailAddress($email)) {
			$parameters['messages']['email'] = (string)$this->l10n->t(
				'Invalid mail address'
			);
		}

		if (empty($password)) {
			$parameters['messages']['password'] = (string)$this->l10n->t(
				'Password cannot be empty'
			);
		}

		$registerToken = $this->config->getUserValue(
			$userId,
			'guests',
			'registerToken',
			false
		);
		// only show token error when there are no others
		if (
			empty($parameters['messages']) &&
			(empty($token) || empty($registerToken) || $registerToken !== $token)
		) {
			$parameters['token'] = $token;
			$parameters['email'] = $email;
			$parameters['messages']['token'] = (string)$this->l10n->t(
				'The token is invalid'
			);
		}

		if (!empty($parameters['messages'])) {
			return new TemplateResponse(
				$this->appName, 'form.password', $parameters, 'guest'
			);
		}

		try {
			$user = $this->userManager->get($userId);

			if (!$user->setPassword($password)) {
				throw new \Exception();
			}

		} catch (\Exception $e){
			return $this->error($e->getMessage());
		}

		$this->config->deleteUserValue($userId, 'guests', 'registerToken');

		// redirect to login
		return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('core.login.showLoginForm'));
	}

}
