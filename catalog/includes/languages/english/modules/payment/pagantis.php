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
  if ( MODULE_PAYMENT_PAGANTIS_DISCOUNT == 'True' ){
    $discount = 1;
  }else{
    $discount = 0;
  }

  if ( MODULE_PAYMENT_PAGANTIS_TESTMODE == 'Test' ){
    $key = MODULE_PAYMENT_PAGANTIS_TK;
  }else{
    $key = MODULE_PAYMENT_PAGANTIS_PK;
  }

  $widget = '';
  if ( MODULE_PAYMENT_PAGANTIS_SIMULATOR == 'True' ) {
    $widget =   '<br/>';
  }

  define('MODULE_PAYMENT_PAGANTIS_TEXT_CATALOG_TITLE', 'Instant financing');  // Payment option title as displayed to the customer

  define('MODULE_PAYMENT_PAGANTIS_TEXT_ADMIN_TITLE', 'Pagantis');

  define('MODULE_PAYMENT_PAGANTIS_TEXT_DESCRIPTION', '<strong>Pagantis</strong><br /><br/>
            Pagantis es una plataforma de financiación online. Escoge Pagantis como tu método de pago para permitir el pago a plazos.
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
