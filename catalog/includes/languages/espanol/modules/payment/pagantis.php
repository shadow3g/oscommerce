<?php
/**
 * Pagantis payment module for oscommerce
 *
 * @package     Pagantis
 * @author      Integrations team <integrations@pagantis.com>
 * @copyright   Copyright (c) 2019  Pagantis (http://www.pagantis.com)
 *
 * @license     Released under the GNU General Public License
 *
 */

  global $customer_id, $order;

  $publicKey = MODULE_PAYMENT_PAGANTIS_PK;
  $widget = '';
  if ( MODULE_PAYMENT_PAGANTIS_SIMULATOR == 'True' )
  {
      $widget = '<script>
    var simulatorId = null;
    function loadSimulator()
    {
        var positionSelector = "'.$positionSelector.'";
        if (positionSelector === \'default\') {
            positionSelector = \'.PagantisSimulator\';
        }
        var priceSelector = "'.$priceSelector.'";
        if (priceSelector === \'default\') {
            priceSelector = \'div.summary.entry-summary span.oscommerce-Price-amount.amount\';
        }
        var quantitySelector = "'.$qantitySelector.'";
        if (quantitySelector === \'default\') {
            quantitySelector = \'div.quantity>input\';
        }
        if (typeof pmtSDK != \'undefined\') {
            pmtSDK.simulator.init({
                publicKey: "'.$publicKey.'",
                type: "'.$simulatorType.'",
                selector: positionSelector,
                itemQuantitySelector: quantitySelector,
                itemAmountSelector: priceSelector
            });
            clearInterval(simulatorId);
        }
    }
    simulatorId = setInterval(function () {
        loadSimulator();
    }, 2000);
</script>
<div class="PagantisSimulator"></div>';
}

  define('MODULE_PAYMENT_PAGANTIS_TEXT_CATALOG_TITLE', 'Instant financing'. $widget);  // Payment option title as displayed to the customer

  define('MODULE_PAYMENT_PAGANTIS_TEXT_ADMIN_TITLE', 'Pagantis');

  define('MODULE_PAYMENT_PAGANTIS_TEXT_DESCRIPTION', '<strong>Pagantis</strong><br /><br/>
            Pay up to 12 comfortable installments with Pagantis. Completely online and sympathetic request, and the answer is immediate!
            <br /><br/>
  <img src="images/icon_popup.gif" border="0">
  <a target="_blank" style="text-decoration: underline; font-weight: bold;" href="https://bo.pagamastarde.com/">Login al panel de Pagantis</a>
  <br/><br/>
  <img src="images/icon_popup.gif" border="0">
  <a target="_blank" style="text-decoration: underline; font-weight: bold;" href="http://docs.pagamastarde.com/">Documentación</a><br /><br />
  Testing Info:<br /><b>Automatic Approval Credit Card Numbers:</b><br />Visa#: 4507670001000009<br />MC#: 5540500001000004<br />
  Expire date: 12 / current year <br />
  CVV: 989 <br />
  <b>Note:</b> These credit card numbers will return a decline in live mode, and an approval in test mode.');


  define('MODULE_PAYMENT_PAGANTIS_TEXT_TYPE', 'Type:');
  define('MODULE_PAYMENT_PAGANTIS_TEXT_ERROR_MESSAGE', 'There has been an error processing your credit card. Please try again.');
  define('MODULE_PAYMENT_PAGANTIS_TEXT_DECLINED_MESSAGE', 'Your credit card was declined. Please try another card or contact your bank for more info.');
  define('MODULE_PAYMENT_PAGANTIS_TEXT_ERROR', 'Credit Card Error!');
