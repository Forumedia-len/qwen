<?php
/**
 * @var string $action
 * @var string $error
 * @var bool $blockingAuth
 */
?>
<form action="<?= $action ?>" method="post" id="login-form-user">
  <input name="tm" type="hidden" value="<?= time()?>">
  <input name="tHc" type="text" style="display: none" value="">
  <div class="login-form-block-input">
    <div class="form-group row">
      <label for="inputEmail" class="col-sm-5 col-form-label"><?= lang('login', 'auth') ?></label>
      <div class="col-sm-7">
        <input name="username" type="text" id="inputEmail" class="form-control" required="" autofocus="">
      </div>
    </div>
    <div class="form-group row">
      <label for="inputPassword" class="col-sm-5 col-form-label"><?= lang('password', 'auth') ?></label>
      <div class="col-sm-7">
        <input type="password" name="password" id="inputPassword" class="form-control" required="">
      </div>
    </div>
    <div class="login-form-block-button">
      <button class="btn btn-block <?= $blockingAuth ? ' disabled' : ''?>" type="submit" <?= $blockingAuth ? ' disabled' : ''?>><?= lang('logIn', 'auth') ?></button>
    </div>
  </div>
</form>
<script>
  function formatTime(sec) {
    const date = new Date(sec * 1000);
    // const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');

    return `${minutes}:${seconds}`;
  }

  function timeRefreshLogin () {
    let tb = document.getElementById('time-box-login')
    let sec = parseInt(tb.getAttribute('data-second'))
    if(sec > 0) {
      tb.setAttribute('data-second', sec-1)
      tb.innerHTML = formatTime(sec)
      window.setTimeout(function () {timeRefreshLogin()}, 1000)
    } else {
      window.location = '<?= site_url(Service::structure()->getPageHrefByKey('login'))?>'
    }
  }

  $(document).ready(function () {
    let blockingAuth = <?= (int) $blockingAuth ?>;
    if(blockingAuth) {
      timeRefreshLogin ()
      document.getElementById('login-form-user').addEventListener('submit', function (event) {
        if(blockingAuth) {
          event.preventDefault();
        }
      }, false)
    }
  })
</script>

