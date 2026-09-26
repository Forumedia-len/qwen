<?if (!empty($data['message'])): ?><div class="alert alert-danger"></div><?endif?>
				<div class="my-4">
					<form class="p-3 border border-primary rounded list-insert" method="post" >
						<?foreach ($data['header'] as $key2 => $val): ?>
						<label for="i_<?=$val?>" class="form-label"><?=$val?></label>
						<input id="i_<?=$val?>" class="border border-info w-100 m-2" type="text" name="<?=$val?>" placeholder="<?=$val?>"/><br/>
						<?endforeach?>
						<input type="hidden" name="action" value="CList">
						<input type="hidden" name="insert" value="Y">
						<div class="btn btn-primary">Вставить</div>
					</form>
				</div>