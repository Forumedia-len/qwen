<?php
/**
 *
 */
$termsLink = '<a href="https://legal.paylater.payone.com/de/terms-of-payment.html" target="_blank" rel="noopener noreferrer">'
  . lang('bnpl_terms_of_payment', 'payment_online') . '</a>';
$dataLink = '<a href="https://legal.paylater.payone.com/de/data-protection-payments.html" target="_blank" rel="noopener noreferrer">'
  . lang('bnpl_data_protection_notice', 'payment_online') . '</a>';
?>

<div class='payone-bnpl-note' style='margin: 8px 0 0 32px;font-size:12px;line-height:1.35;position: relative'>
  <?= lang('bnpl_checkout_note', 'payment_online',
    [
      'terms_link' => $termsLink,
      'data_link'  => $dataLink,
    ]
  ) ?>
</div>