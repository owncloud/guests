<?php
/**
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

use OCA\Guests\GuestsHandler;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;

class RegisterController extends Controller {

	/**
	 * @var IRequest
	 */
	protected $request;

	/**
	 * @var IL10N
	 */
	private $l10n;

	/**
	 *  @var IURLGenerator
	 */
	private $urlGenerator;

	/**
	 * @var GuestsHandler
	 */
	private $handler;

	/**
	 * RegisterController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param IUrlGenerator $urlGenerator
	 * @param GuestsHandler $handler
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IL10N $l10n,
		IUrlGenerator $urlGenerator,
	    GuestsHandler $handler
	) {
		parent::__construct($appName, $request);

		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->handler = $handler;
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
		$result = $this->handler->validateGuest($email, $token);
		if (!$result) {
			$errorMessages['token'] = (string)$this->l10n->t(
				'The token is invalid for given email address'
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
		$parameters = [];

		if (empty($password)) {
			$parameters['messages']['password'] = (string)$this->l10n->t(
				'Password cannot be empty'
			);
			return new TemplateResponse(
				$this->appName, 'form.password', $parameters, 'guest'
			);
		}

		try {
			$result = $this->handler->updateGuest($email, $password, $token);
		} catch (\Exception $e){
			$parameters['email'] = $email;
			$parameters['messages']['password'] = $e->getMessage();
			$parameters['token'] = $token;
			$parameters['postAction'] = $this->urlGenerator->linkToRouteAbsolute(
				'guests.register.register'
			);
			return new TemplateResponse(
				$this->appName, 'form.password', $parameters, 'guest'
			);
		}

		if (!$result) {
			$parameters['messages']['token'] = (string)$this->l10n->t(
				'The token is invalid for given email address'
			);
			return new TemplateResponse(
				$this->appName, 'form.password', $parameters, 'guest'
			);
		}

		// redirect to login
		return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('core.login.showLoginForm'));
	}

}
