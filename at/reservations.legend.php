<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
		<td width="50%">
			<table cellspacing="6" cellpadding="0" border="0">
				<tr>
					<td><div class="calendarLegend current">00</div></td>
					<td><?= lang('Current date', 'reservations')?></td>
				</tr>
				<tr>
					<td><div class="calendarLegend selected">00</div></td>
					<td><?= lang('Selected day', 'reservations')?></td>
				</tr>
				<tr>
					<td><div class="calendarLegend unavaliable">00</div></td>
					<td><?= lang('Not yet released for reservation', 'reservations')?></td>
				</tr>
				<tr>
					<td><div class="calendarLegend holiday">00</div></td>
					<td><?= lang('Holiday', 'reservations')?></td>
				</tr>
			</table>
		</td>
		<td width="50%">
			<table border="0" cellspacing="6" cellpadding="0">
				<tr><th class="period_unavaliable period_legend white"><?= lang('Expired', 'reservations')?></th></tr>
				<tr><th class="period_ordered period_legend white"><?= lang('Occupied', 'reservations')?></th></tr>
				<tr><th class="period_blocked period_legend"><?= lang('Blocked by operator', 'reservations')?></th></tr>
			</table>
			<table border="0" cellspacing="6" cellpadding="0">
				<tr>
					<td valign="top"><img src="<?= base_url(paths()->getAssetsDir('images/order_remove.gif'))?>" width="17" height="17"></td>
					<td align="center" valign="top">&nbsp;-&nbsp;</td>
					<td class="text"><?= lang('Cancel reservation', 'reservations')?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>