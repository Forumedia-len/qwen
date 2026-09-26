<?php
/**
 * @var string     $reference
 * @var UrlDataDto $urlDataDto
 * @var string     $orderType
 */

use AC\core\modules\payment\payone\entities\dto\UrlDataDto;

?>
<div class='status-message'>
  <img src="<?= cdn_url(paths()->getAssetsDir('images/ajax_loader.gif', 'common')) ?>" alt=''/>
  <p>
    <strong><?= lang('We check the payment status', 'payment_online') ?></strong>
  </p>
  <p>
    <?= lang('It only takes a few seconds', 'payment_online') ?>
  </p>
</div>
<div class="timeout-message" style="display: none">
  <strong><?= lang('Payment is being processed', 'payment_online') ?></strong><br><br>
  <p style="margin-bottom: 15px;">
    <?= lang('We have received your payment and are currently processing it',
      'payment_online') ?>
  </p>
</div>
<p style='text-align: center'>
  <a href='<?= site_url() ?>'>
    <?= lang('home') ?>
  </a>
</p>

<script>
  // Конфигурация
  const reference = '<?= $reference ?>'
  const orderType = '<?= $orderType ?>'
  const successUrl = '<?= $urlDataDto->successurl ?>'
  const backUrl = '<?= $urlDataDto->backurl ?>'
  const errorUrl = '<?= $urlDataDto->errorurl ?>'
  const apiUrl = window.baseUrl + 'mapi/api.php?action=statusPayment&reference=' + encodeURIComponent(reference) + '&type=' + encodeURIComponent(orderType)
  const checkInterval = 2000
  const maxAttempts = 30
  let attemptCount = 0
  let isProcessing = false
  let hasRedirected = false

  function redirect (url) {
    if (!hasRedirected) {
      hasRedirected = true
      window.location.href = url
    }
  }

  async function checkPaymentStatus () {
    if (isProcessing || hasRedirected) return false

    isProcessing = true

    try {
      const response = await fetch(apiUrl, {method: 'GET', cache: 'no-cache'})
      let data
      try {
        data = await response.json()
      } catch (jsonError) {
        console.warn('Invalid JSON response:', jsonError)
        return false
      }

      if (data.error) {
        console.warn('API error:', data.error)
        return false
      }

      switch (data.status) {
        case 'OK':
          redirect(successUrl)
          return true
        case 'CANCEL':
          redirect(backUrl)
          return true
        case 'FALSE':
          redirect(errorUrl + '?messageCode=7&status=FALSE')
          return true
        case 'FAILED':
          redirect(errorUrl + '?messageCode=7&status=FAILED')
          return true
        case 'ERROR':
          redirect(errorUrl + '?messageCode=6&status=ERROR')
          return true
        case 'WAIT':
          return false
        default:
          console.warn('Unknown status:', data.status)
          return false
      }
    } catch (error) {
      console.warn('Fetch error (will retry):', error)
      return false
    } finally {
      isProcessing = false
    }
  }

  function startRegularChecks () {
    const intervalId = setInterval(async () => {
      attemptCount++
      const completed = await checkPaymentStatus()

      if (completed || attemptCount >= maxAttempts) {
        clearInterval(intervalId)
        if (!completed) {
          handleTimeout()
        }
      }
    }, checkInterval)

    setTimeout(() => {
      if (!hasRedirected) checkPaymentStatus()
    }, 500)
  }

  function handleTimeout () {
    document.querySelector('.status-message').style.display = 'none'
    document.querySelector('.timeout-message').style.display = 'block'

    const backgroundCheck = setInterval(async () => {
      if (hasRedirected) {
        clearInterval(backgroundCheck)
        return
      }
      await checkPaymentStatus()
    }, 10000)
  }

  document.addEventListener('DOMContentLoaded', () => {
    startRegularChecks()
  })
</script>