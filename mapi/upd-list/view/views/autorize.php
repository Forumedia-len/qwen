<main>
	<div class="autorize container d-flex justify-content-center align-items-center" >
	<form action="<?=BASE_URL?>?action=CAutorize" method="post" >
		<div>
			<label for="user">Пользователь:</label>
		<input id="user" type="text" name="user" class="form-control m-3" value="">
	</div>
	<div>
		<label for="userPassword">Пароль:</label>
		<input id="userPassword" type="password" name="password" value="" class="form-control m-3">
	</div>
	<div>
		<input type="submit" class="btn btn-primary m-3" value="Войти">
	</div>
	</form>
	</div>
</main>
