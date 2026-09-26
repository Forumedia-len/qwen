	<div class="saves my-4 p-3 border border-primary rounded">
		<h2>Сохранки</h2>
		<div><label class="p-2 form-label" for="base">База</label><input id="base" class="border border-dark w-30 m-2 form-check-input" type="checkbox" value="base" name="chk_base" checked></div>
		<div><label class="p-2 form-label" for="site">Сайт</label><input id="site" class="border border-dark w-30 m-2 form-check-input" type="checkbox"  value="site" name="chk_site" checked></div>
		<div class="accordion" id="accordionPhp">
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
						<div class="p-2"><label class="p-2 form-label">Поле базы</label><input class="border border-dark w-30" type="text" value="base" name="field_base"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="btn btn-primary my-3">Выполнить</div>
	</div>