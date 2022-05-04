<?php declare(strict_types=1);
/**
 * ownCloud
 *
 * @author Sagar Gurung <sagar@jankaritech.com>
 * @copyright Copyright (c) 2017 Sagar Gurung sagar@jankaritech.com
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
 * page where the guests users are grouped under a virtual group in the user manager
 */
class GuestsPage extends OwncloudPage {

	/**
	 * @var string $path
	 */
	protected $path = 'index.php/settings/admin?sectionid=sharing#guests';
	protected $guestsSharingBlockDomainsInputFieldId = 'guestSharingBlockDomains';

	/**
	 * get the blocked domains from sharing with guests
	 *
	 * @return string
	 */
	public function getBlockedDomainsFromSharingWithGuests(): string {
		$blockedDomainsSharingWithGuests = $this->findById($this->guestsSharingBlockDomainsInputFieldId);
		$this->assertElementNotNull(
			$blockedDomainsSharingWithGuests,
			__METHOD__ .
			" id $this->guestsSharingBlockDomainsInputFieldId could not find input field for blocked domains from sharing with guests"
		);
		return $blockedDomainsSharingWithGuests->getValue();
	}

	/**
	 * Set the blocked domains from sharing with guests
	 *
	 * @param string $blockedDomains
	 *
	 * @return void
	 * @throws Exception
	 */
	public function setBlockedDomainsFromSharingWithGuests(string $blockedDomains): void {
		$blockedDomainsSharingWithGuests = $this->findById($this->guestsSharingBlockDomainsInputFieldId);
		$this->assertElementNotNull(
			$blockedDomainsSharingWithGuests,
			__METHOD__ .
			" id $this->guestsSharingBlockDomainsInputFieldId could not find input field for blocked domains from sharing with guests"
		);
		$this->fillField($this->guestsSharingBlockDomainsInputFieldId, $blockedDomains);
	}
}
