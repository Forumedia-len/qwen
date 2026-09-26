<main>

	<script>
		console.log(<?=json_encode($data, JSON_UNESCAPED_UNICODE)?>);
	</script>

	<div class="container">
		<div class="accordion" id="accordionExample">
			<div class="accordion-item">

				<h2 class="accordion-header" id="heading3">
					<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
						SQL запрос
					</button>
				</h2>

				<div id="collapse3" class="accordion-collapse collapse" aria-labelledby="heading3" data-bs-parent="#accordionExample">
					<div class="accordion-body">
						<?include DIR_BASE."view/views/blocks/block_sql.php";?>
					</div>
				</div>
			</div>

			<div class="accordion-item">

				<h2 class="accordion-header" id="heading4">
					<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
						PHP комманда
					</button>
				</h2>

				<div id="collapse4" class="accordion-collapse collapse" aria-labelledby="heading4" data-bs-parent="#accordionExample">
					<div class="accordion-body">

						<?include DIR_BASE."view/views/blocks/block_php.php";?>
					</div>
				</div>
			</div>

			<div class="accordion-item">

				<h2 class="accordion-header" id="heading5">
					<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
						Сохранки
					</button>
				</h2>
				<div id="collapse5" class="accordion-collapse collapse" aria-labelledby="heading5" data-bs-parent="#accordionExample">
					<div class="accordion-body">

						<?include DIR_BASE."view/views/blocks/block_save.php";?>

					</div>
				</div>
			</div>

			<div class="accordion-item">

				<h2 class="accordion-header" id="heading5">
					<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse7" aria-expanded="false" aria-controls="collapse7">
						Перезаписать файлы и применить sql к базе данных
					</button>
				</h2>
				<div id="collapse7" class="accordion-collapse collapse" aria-labelledby="heading7" data-bs-parent="#accordionExample">
					<div class="accordion-body">

						<?include DIR_BASE."view/views/blocks/block_replase.php";?>

					</div>
				</div>
			</div>

			<div class="accordion-item">

				<h2 class="accordion-header" id="heading1">
					<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
						Добавить строку
					</button>
				</h2>

				<div id="collapse1" class="accordion-collapse collapse" aria-labelledby="heading1" data-bs-parent="#accordionExample">
					<div class="accordion-body">
						<?include DIR_BASE."view/views/blocks/block_add.php";?>
					</div>
				</div>
			</div>


			<div class="accordion-item">

				<h2 class="accordion-header" id="heading2">
					<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
						Список
					</button>
				</h2>

				<div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2" data-bs-parent="#accordionExample">
					<div class="accordion-body">

						<?include DIR_BASE."view/views/blocks/block_list.php";?>
					</div>
				</div>
			</div>



		</div>

		<div class="my-5 msg alert alert-info"></div>
	</div>


</main>