<?php
/**
 * @var string $action
 * @var string $delay
 */

?>
<?php if (USE_RECAPTCHA_V4): ?>
  <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response"/>
  <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_CLIENT_KEY ?>"></script>
  <script>
    function getRecaptcha() {
      grecaptcha.ready(function () {
        grecaptcha.execute('<?=RECAPTCHA_CLIENT_KEY?>', {action: '<?= ($action ?: 'registration') ?>'}).then(function (token) {
          document.getElementById('g-recaptcha-response').value = token
        })
      })
    }

    getRecaptcha()
    setInterval(function () {
      getRecaptcha()
    }, <?= ($delay ?: 60000)?>) // запускаем генерацию токена из-за политики гугла (1 минута жизни токена по умолчанию)
  </script>
<?php endif ?>