<div class="php my-4 p-3 border border-primary rounded">
	<form>
		<h2>PHP комманда</h2>
		<textarea class="form-control-plaintext border border-dark p-2" name="command_php" rows="10"><?=$data['php'];?>
	</textarea>
	<div class="list-group my-4">

		<h4>Список файлов на отправку</h4>
		<?foreach($data['files'] as $key=>$name):?>
		<div class="list-group-item">
			<input class="form-check-input" type="checkbox" id="file_<?=$key?>" value="<?=$name?>" name="files[]">
			<label class="form-check-label" for="file_<?=$key?>"><?=$name?></label>
		</div>
		<?endforeach?>
	</div>
	<div class="accordion my-3" id="accordionPhp">
		<div class="accordion-item">
			<h2 class="accordion-header" id="pheading3">
				<button class="accordion-button collapsed bg-info" type="button" data-bs-toggle="collapse" data-bs-target="#pcollapse3" aria-expanded="false" aria-controls="pcollapse3">
					Параметры колонок
				</button>
			</h2>

			<div id="pcollapse3" class="accordion-collapse collapse" aria-labelledby="pheading3" data-bs-parent="#accordionPhp">
				<div class="accordion-body">
					<div class="p-2"><label class="p-2 form-label">Протокол</label><input class="border border-dark w-30" type="text" value="protocol" name="protocol"></div>
					<div class="p-2"><label class="p-2 form-label">Поле site</label><input class="border border-dark w-30" type="text" value="site" name="site"></div>
					<div class="p-2"><label class="p-2 form-label">Поле ftp хоста</label><input class="border border-dark w-30" type="text" value="ftp_host" name="host"></div>
					<div class="p-2"><label class="p-2 form-label">Поле ftp логина</label><input class="border border-dark w-30" type="text" value="ftp_user" name="login"></div>
					<div class="p-2"><label class="p-2 form-label">Поле ftp пароля</label><input class="border border-dark w-30" type="text" value="ftp_pass" name="password"></div>
					<div class="p-2"><label class="p-2 form-label">Поле ftp пути к сайту</label><input class="border border-dark w-30" type="text" value="ftp_path" name="path"></div>
				</div>
			</div>
		</div>
	</div>
	<div class="btn btn-primary">Выполнить</div>
</form>
</div>