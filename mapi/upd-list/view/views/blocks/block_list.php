<h2>Список</h2>

<div class="btn-group">
	<?foreach($data['tab_list'] as $list):?>
	<a href="<?=BASE_URL?>?list=<?=$list['name']?>" class="btn my-3 btn-lg btn-primary <?=isset($list['current'])?'active':''?>"><?=$list['name']?></a>
	<?endforeach;?>
</div>
<table class="list-table" width="100%" data-list="<?=empty(MRequest::get('list'))?'list':MRequest::get('list');?>">
	<tr class="border border-primary ">
		<th class="item_no p-1 text-center">№</th>
		<th class="item_check p-1 text-center"><input class="form-check-input p-2" type="checkbox" name="fix_all" value="Y"></th>
		<th class=" item-val p-1">
			<?foreach ($data['header'] as $key2 => $val): ?>
			<span><?=$val?></span><br/>
			<?endforeach?>
		</th>
		<th class="item_update p-1 text-center"></th>
		<th class="item_delete p-1 text-center"></th>
	</tr>
	<?foreach ($data['list'] as $key => $site): ?>

	<tr class="border border-primary">
		<td class="item_no p-1 text-center"><?=$key + 1?>.</td>
		<td class="item_check p-1 text-center"><input class="form-check-input p-2" type="checkbox" name="fix" value="<?=$key?>"></td>
		<td class=" item-val p-1">
			<form id="line_<?=$key?>">
				<?foreach ($site as $key2 => $val): ?>
				<input class="m-1 border-0 w-100" type="text" name="<?=$key2?>" readonly value="<?=$val?>"  placeholder="<?=$key2?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?=$key2?>"><br/>
				<?endforeach?>
			</form>
		</td>
		<td class="item_update p-1 text-center" data-key="line_<?=$key?>">
			<i class="item-upd bi bi-pencil-square m-3 fs-4"></i>
			<input type="hidden" name="item_id" value="<?=$key?>">
		</td>
		<td class="item_delete p-1 text-center">
			<i class="item-del bi bi-x-square-fill m-3 fs-4"></i>
			<input type="hidden" name="item_id" value="<?=$key?>">
		</td>
	</tr>

	<?endforeach?>
</table>