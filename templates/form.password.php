<?php /** @var $l \OCP\IL10N */ ?>

<div id="body-login">
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
				<?php p($_['user_autofocus'] ? '' : 'autofocus'); ?>
				autocomplete="off" autocapitalize="off" autocorrect="off" required>
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