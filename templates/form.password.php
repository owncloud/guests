<?php
/**
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
?>
<div>
<form action="<?php p($_['postAction']); ?>" name="register" method="post">
	<fieldset>
		<?php foreach($_['messages'] as $message): ?>
			<div class="warning">
				<?php p($message); ?><br>
			</div>
		<?php endforeach; ?>
		<p class="grouptop<?php if (!empty($_['invalidpassword'])) { ?> shake<?php } ?>">
			<input type="text" name="email" id="email"
				placeholder="<?php p($l->t('Email')); ?>"
				value="<?php p($_['email']); ?>"
				autocomplete="off" autocapitalize="off" autocorrect="off" required>
			<label for="email" class="infield"><?php p($l->t('Email')); ?></label>
		</p>

		<p class="groupbottom<?php if (!empty($_['invalidpassword'])) { ?> shake<?php } ?>">
			<input type="password" name="password" id="password" value=""
				placeholder="<?php p($l->t('Password')); ?>"
				autocomplete="off" autocapitalize="off" autocorrect="off" required autofocus>
			<label for="password" class="infield"><?php p($l->t('Password')); ?></label>
		</p>
		<div class="buttons">
			<input type="submit" id="submit" value="<?php p($l->t('Set password')); ?>"/>
			<input type="hidden" name="token" value="<?php p($_['token']) ?>">
			<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
		</div>
	</fieldset>
</form>
</div>
