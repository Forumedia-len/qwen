<?php
/**
 * Payla DCS (device fingerprinting) snippet for Payone BNPL (PDD).
 *
 * @var string $partnerId
 * @var string $merchantId
 * @var string $environment "t" (test) | "p" (prod)
 * @var string $snippetToken
 * @var string $deviceTokenInputId
 * @var string $nonceInputId
 * @var string $nonce
 */
?>

<input type="hidden" name="bnpl_nonce" id="bnpl_nonce" value="<?= htmlspecialchars($nonce) ?>">
<input type="hidden" name="device_token" id="device_token" value="<?= $snippetToken ?>">
<script id="paylaDcs" type="text/javascript"
        src="https://d.payla.io/dcs/<?= rawurlencode($partnerId) ?>/<?= rawurlencode($merchantId) ?>/dcs.js"></script>
<script>
      var paylaDcsT = paylaDcs.init(<?= $environment ?>, <?= $snippetToken ?>)
</script>
<link id="paylaDcsCss" type="text/css" rel="stylesheet"
      href="https://d.payla.io/dcs/dcs.css?st=<?= rawurlencode($snippetToken) ?>&pi=<?= rawurlencode($partnerId) ?>&psi=<?= rawurlencode($merchantId) ?>&e=<?= rawurlencode($environment) ?>">
<?php /*
$environment = 't'; // "t" for TEST, "p" for PROD
$payla_partner_id = 'e7yeryF2of8X';
$partner_merchant_id = 'test-1'; // REPLACE individually per Merchant by Payone Merchant-ID
$snippet_token = $payla_partner_id . '_' . $partner_merchant_id . '_' . guidv4(); // REPLACE guidv4() by a session_id (which should be unique per checkout experience) or an appropriate GUIDv4 function
?>

<script id="paylaDcs" type="text/javascript" src="https://d.payla.io/dcs/<?php
echo $payla_partner_id; ?>/<?php
echo $partner_merchant_id; ?>/dcs.js"></script>
<script>
  var paylaDcsT = paylaDcs.init("<?php echo $environment; ?>", "<?php echo $snippet_token; ?>");
</script>

<link id="paylaDcsCss" type="text/css" rel="stylesheet" href="https://d.payla.io/dcs/dcs.css?st=<?php
echo $snippet_token; ?>&pi=<?php
echo $payla_partner_id; ?>&psi=<?php
echo $partner_merchant_id; ?>&e=<?php
echo $environment; ?>"> */