<div class="replase my-4 p-3 border border-primary rounded">
	<h2>Перезаписать файлы и применить sql к базе данных</h2>
	<h4>SQL запрос</h4>
	<textarea class="form-control-plaintext border border-dark p-2" name="replase_sql" rows="10"><?=$data['site_sql'];?>	
	</textarea>
	<div class="alert alert-info mt-4">
		<?foreach($data['header'] as $val): ?>
		%<?=$val?>%, 
		<?endforeach?>
	</div>
	<div class="my-4">
		<input class="form-check-input mx-2 p-2" type="checkbox" id="chk_php_replase" value="Y" name="chk_php_replase">
		<label class="form-check-label" for="chk_php_replase">Перезаписать файлы</label>
	</div>
	<div class="my-4">
		<input class="form-check-input mx-2 p-2" type="checkbox" id="chk_sql_replase" value="Y" name="chk_sql_replase">
		<label class="form-check-label" for="chk_sql_replase">Применить sql</label>
	</div>
	<div class="accordion" id="accordionReplase">
		<div class="accordion-item">
			<h2 class="accordion-header" id="rheading3">
				<button class="accordion-button collapsed bg-info" type="button" data-bs-toggle="collapse" data-bs-target="#rcollapse3" aria-expanded="false" aria-controls="rcollapse3">
					Параметры колонок
				</button>
			</h2>

			<div id="rcollapse3" class="accordion-collapse collapse" aria-labelledby="rheading3" data-bs-parent="#accordionReplase">
				<div class="accordion-body">
					<div class="p-2"><label class="p-2 form-label">Протокол</label><input class="border border-dark w-30" type="text" value="protocol" name="protocol"></div>
					<div class="p-2"><label class="p-2 form-label">Поле site</label><input class="border border-dark w-30" type="text" value="site" name="site"></div>
					<div class="p-2"><label class="p-2 form-label">Поле ftp хоста</label><input class="border border-dark w-30" type="text" value="ftp_host" name="host"></div>
					<div class="p-2"><label class="p-2 form-label">Поле ftp логина</label><input class="border border-dark w-30" type="text" value="ftp_user" name="login"></div>
					<div class="p-2"><label class="p-2 form-label">Поле ftp пароля</label><input class="border border-dark w-30" type="text" value="ftp_pass" name="password"></div>
					<div class="p-2"><label class="p-2 form-label">Поле ftp пути к сайту</label><input class="border border-dark w-30" type="text" value="ftp_path" name="path"></div>
					<div class="p-2"><label class="p-2 form-label">Поле базы</label><input class="border border-dark w-30" type="text" value="base" name="field_base"></div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="btn btn-primary my-3">Выполнить</div>
</div>
