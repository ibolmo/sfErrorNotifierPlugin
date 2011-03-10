<h2>Summary</h1>

<h3>Data</h3>
<dl>
	<?php foreach ($data as $key => $value): ?>
		<dt><?php echo ucwords($key) ?></dt>
		<dd><pre><?php echo $value ?></pre></dd>
	<?php endforeach ?>
</dl>

<?php if (isset($user) && $user): ?>	
	<h3>User</h3>
	<dl>
		<?php foreach ($user->getAttributeHolder()->getAll() as $key => $value): ?>
			<dt><?php echo $key ?></dt>
			<dd>
				<?php if (is_object($value)): ?>
					<?php if (method_exists($value, '__toString')): ?>
						<?php echo $value ?>
					<?php else: ?>
						<?php echo get_class($value) ?>
					<?php endif ?>
				<?php elseif (is_array($value)): ?>
					<ul>
						<?php foreach ($value as $value): ?>
							<li><?php echo $value ?></li>
						<?php endforeach ?>
					</ul>
				<?php endif ?>
			</dd>
		<?php endforeach ?>
	</dl>

	<ul>
		<?php foreach (method_exists($user, 'listCredentials') ? $user->listCredentials() : $user->getCredentials() as $credential): ?>
			<li><?php echo $credential ?></li>
		<?php endforeach ?>
	</ul>
<?php endif ?>
