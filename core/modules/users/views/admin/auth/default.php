<?php
/***
 * @var string $error
 * @var string $typeAuth
 * @var array $params
 */
?>
<div class="form-signin">
  <div class="login-form-header row">
    <div class="login-form-logo-img col-sm-5">
      <img src="<?= base_url(paths()->getAssetsDir('images/login/forumedia.png')) ?>" alt="logo">
    </div>
    <div class="login-form-logo-text col-sm-7">
      we are your software
    </div>
  </div>
  <div class="login-form-content">
    <div class="login-form-content-header">
      <p class="active-court"><span>A</span>ctive <span>C</span>ourt</p>
      <p>reservation system</p>
    </div>

    <?= view()->render('auth\\' . (!empty($typeAuth) && $typeAuth === '2FA' ? '_2fa' : '_form'), $params)?>
    <div>
      <?= (!empty($error) ? '<p style="color:red;">' . $error . '</p>' : '') ?>
    </div>
  </div>
</div>
