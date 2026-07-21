<?php
/**
 * ownCloud
 *
 * @copyright (C) 2026 ownCloud GmbH
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

namespace OCA\Guests\Tests\Unit;

use OCA\Guests\AppWhitelist;
use Test\TestCase;

/**
 * Class AppWhitelistTest
 *
 * @package OCA\Guests\Tests\Unit
 */
class AppWhitelistTest extends TestCase {
	/**
	 * Route to requested-app mapping.
	 *
	 * @return array
	 */
	public function requestedAppData() {
		return [
			// regression case from issue #696: the core 2FA challenge route
			// must map to the (whitelisted) core app so guests can log in
			'2fa challenge' => ['/login/challenge/totp', 'core'],
			'login' => ['/login', 'core'],
			'logout' => ['/logout', 'core'],
			'apps/files' => ['/apps/files/', 'files'],
			'core' => ['/core/something', 'core'],
			'settings' => ['/settings/users', 'settings'],
			'avatar' => ['/avatar/foo/128', 'avatar'],
			'heartbeat' => ['/heartbeat', 'heartbeat'],
			'dav comments' => ['/dav/comments', 'comments'],
			'dav files' => ['/dav/files/user/path', 'files'],
			'unrecognised' => ['/ocs/v2.php/apps/foo', false],
		];
	}

	/**
	 * @dataProvider requestedAppData
	 *
	 * @param string $url
	 * @param string|false $expectedApp
	 *
	 * @return void
	 */
	public function testGetRequestedApp($url, $expectedApp) {
		$app = self::invokePrivate(AppWhitelist::class, 'getRequestedApp', [$url]);
		self::assertSame($expectedApp, $app);
	}
}
