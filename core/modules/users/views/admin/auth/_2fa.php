<?php
/**
 * @var string $action
 * @var string $actionBack
 * @var string $email
 * @var int $timeRefreshSend
 */
?>
<form action="<?= $action?>" method="POST">
  <input type="hidden" name="check" value="1">
  <div class="login-form-block-input">
    <div style="justify-content: center;font-size: 14px; color: #27526f; font-weight: normal">
     <?= lang('A confirmation code has been sent to your e-mail address', 'users', ['email' => $email])?>
    </div>
    <div class="row" style="justify-content: center;">
      <label for="token" style="font-size: 14px; color: #27526f; font-weight: normal"> <?= lang('Please enter the code you were sent', 'users')?>:</label>
    </div>
    <div class="row" style="justify-content: center;">
      <input type="text" name="token" id="token" class="form-control" required="required" autofocus autocomplete="one-time-code" autocapitalize="off" inputmode="numeric" pattern="[0-9]*" minlength="6" maxlength="6" style="width: 175px"/>
    </div>
    <div class="login-form-block-button">
      <button class="btn btn-block" style="margin: 20px auto 0;right: 0" type="submit"><?= lang('verify code', 'users') ?></button>
    </div>
    <div id="verify-send-code-repeat" style="padding: 20px 0">

      <p><?= lang('Resend it through', 'users') . ' <strong><span id="time-box-2fa" data-second="'. $timeRefreshSend .'"></span></strong>' ?></p>
    </div>
    <div style="">
      <a style="color: #27526f; text-decoration: underline; cursor: pointer" href="<?= $actionBack ?>"><?= lang('back') ?></a>
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

  function timeRefresh () {
    let tb = document.getElementById('time-box-2fa')
    let sec = parseInt(tb.getAttribute('data-second'))
    if(sec > 0) {
      tb.setAttribute('data-second', sec-1)
      tb.innerHTML = formatTime(sec)
      window.setTimeout(function () {timeRefresh()}, 1000)
    } else {
      document.getElementById('verify-send-code-repeat').innerHTML = '<a style="color:#27526f;cursor:pointer" id="send_new_code"><?= lang('Send new code', 'users')?></a>'
      document.getElementById('send_new_code').addEventListener('click', function () {
        let dataObject = {}
        dataObject.sendNewCode = 1
         sendAjax('<?= site_url(Service::structure()->getPageHrefByKey('login_2FA'))?>', dataObject, function (data) {
           if(data) {
             document.getElementById('verify-send-code-repeat').innerHTML = '<p><?= lang('Resend it through', 'users')?> <strong><span id="time-box-2fa" data-second="'+ data.timeRefreshSend +'"></span></strong></p>'
             timeRefresh();
           }
        }, 'post', 'json')
      }, false)
    }
  }

  function redirectTo() {
      window.location = '<?= site_url(Service::structure()->getPageHrefByKey('login'))?>'
  }

  function redirectId() {
    return window.setTimeout(redirectTo, parseInt('<?= 1000 * config('auth')->numberOfSecondsToReload2FA() ?>'))
  }


  $(document).ready(function () {
    timeRefresh()
    let redirectTimeoutId = redirectId()
    window.addEventListener('mousemove', function() {
      window.clearTimeout(redirectTimeoutId)
      redirectTimeoutId = redirectId()
    }, true)

  })
</script>
