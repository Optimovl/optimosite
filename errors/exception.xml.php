<?= '<?xml version="1.0" encoding="utf-8"?>'; ?>
<result xmlns:xlink="http://www.w3.org/TR/xlink">
	<data>
		<error code="<?= $exception->code; ?>" type="<?= $exception->type; ?>"><?= $exception->message; ?></error>
		<?php

		if (DEBUG_SHOW_BACKTRACE):
			?>
			<backtrace><?php

			$traces = explode("\n", $exception->traceAsString);

			foreach ($traces as $trace):
				?>
				<trace><?= $trace ?></trace><?php
			endforeach;

			?></backtrace><?php
		endif;
		?>
	</data>
</result>
