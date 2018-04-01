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
namespace OCA\Guests\Controller;

use OC\AppFramework\Http;
use OCA\Guests\GuestsHandler;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;

class UsersController extends Controller {

	/**
	 * @var IL10N
	 */
	private $l10n;

	/**
	 * @var GuestsHandler
	 */
	private $handler;

	/**
	 * UsersController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param GuestsHandler $handler
	 * @param IL10N $l10n
	 */
	public function __construct($appName,
								IRequest $request,
								GuestsHandler $handler,
								IL10N $l10n
	) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->handler = $handler;
	}

	/**
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param $displayName
	 * @param $email
	 * @param $username
	 * @return DataResponse
	 */
	public function create($displayName, $email, $username) {
		// Decode email from the web form
		$email = trim(urldecode($email));

		// Generate token which will be used to create a guest and update him later by the user
		$token = $this->handler->createToken();

		// Create guest which would require token to update its account
		$result = $this->handler->createGuest($email, $displayName, $token);
		if ($result) {
			return new DataResponse(
				[
					'message' => (string)$this->l10n->t(
						'User successfully created'
					)
				],
				Http::STATUS_CREATED
			);
		} else {
			$errorMessages['email'] = (string)$this->l10n->t(
				'Invalid mail address or user with that email already exists.'
			);
			return new DataResponse(
				[
					'errorMessages' => $errorMessages
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}
	}

}
