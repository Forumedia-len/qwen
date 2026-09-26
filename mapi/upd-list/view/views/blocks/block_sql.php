<div class="my-4 p-3 border border-primary rounded">
	<form class="sql">
		<h2>SQL запрос</h2>
		<textarea class="form-control-plaintext border border-dark p-2" name="command_sql" rows="10"><?=$data['sql'];?>
	</textarea>
	<div class="alert alert-info mt-4">
		<?foreach($data['header'] as $val): ?>
		%<?=$val?>%, 
		<?endforeach?>
	</div>
	<div class="p-2"><label class="p-2 form-label">Поле базы</label><input class="border border-dark w-30" type="text" value="base" name="field_base"></div>
	<div class="btn btn-primary">Выполнить</div>
</form>
</div>