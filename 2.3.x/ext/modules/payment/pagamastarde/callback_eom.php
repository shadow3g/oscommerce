<?php
/**
* PagaMasTarde payment module for oscommerce
*
* @package     PagaMasTarde
* @author      Epsilon Eridani CB <contact@epsilon-eridani.com>
* @copyright   Copyright (c) 2015  PagaMasTarde (http://www.pagamastarde.com)
*
* @license     Released under the GNU General Public License
*
*/

chdir('../../../../');
require('includes/application_top.php');

if (!defined('MODULE_PAYMENT_PAGAMASTARDE_EOM_STATUS') || (MODULE_PAYMENT_PAGAMASTARDE_EOM_STATUS  != 'True')) {
  exit;
}


/*
* Notificacion desde PagaMasTarde
*/

$json = file_get_contents('php://input');
$notification = json_decode($json, true);


if(isset($notification['event']) && $notification['event'] == 'sale.created')  {

  // customer is in the pagantis gateway page, but the payment is not complete
  // se ha abierto la pagina de pago, pero todavia no se ha realizado el cobro
  exit;
}

$mode = ((MODULE_PAYMENT_PAGAMASTARDE_EOM_MODE == 'Test') ? 'test' : 'real');
if ( $mode == 'real'){
  $pagamastarde_secret = trim( MODULE_PAYMENT_PAGAMASTARDE_EOM_SECRET );
}else{
  $pagamastarde_secret = trim( MODULE_PAYMENT_PAGAMASTARDE_EOM_TEST_SECRET );
}
$signature_check = sha1($pagamastarde_secret.$notification['account_id'].$notification['api_version'].$notification['event'].$notification['data']['id']);
if ($signature_check != $notification['signature'] ){
  //hack detected - not implemented yet
  die( 'Fallo en el proceso de pago. Su pedido ha sido cancelado.' );
  exit;
}


if(isset($notification['event']) && $notification['event'] == 'charge.created')  {

  // recoger informacion del pedido
  $order_id_from_pagantis = $notification['data']['order_id'];

  if(strpos($order_id_from_pagantis, 'upsell') === false) {
    //standard payment

    $order_query = tep_db_query("select * from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id_from_pagantis . "' ");

    $order = false;
    if (tep_db_num_rows($order_query) > 0) {

      $order = tep_db_fetch_array($order_query);
    }


    // comprobacion de seguridad - importe del pedido
    $order_total_query = tep_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$order_id_from_pagantis . "' and class = 'ot_total' limit 1");
    $order_total = tep_db_fetch_array($order_total_query);

    $order_total_amount = (int) ( $order_total['value'] * 100 );
    $pagantis_order_total_amount = $notification['data']['amount'];

    $total_amount_error = abs($order_total_amount - $pagantis_order_total_amount);

    if($total_amount_error > 1)
    {
      // error o intento de fraude
      $order = false;
    }

    if($order)
    {

      // recoger informacion de la sesion del cliente
      // ahora estamos en una sesion nueva abierta desde el servidor de Pagantis
      $customer_session_id = trim($order['cc_owner']);

      if($customer_session_id!='')
      {

        // Actualizar estado del pedido
        // Actualizar historial del pedido
        // Quitar la referencia a la sesion que se incluyo en el pedido

        $order_query = tep_db_query("select orders_status, currency, currency_value from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id_from_pagantis . "' ");
        if (tep_db_num_rows($order_query) > 0) {
          $order = tep_db_fetch_array($order_query);

          if ($order['orders_status'] == MODULE_PAYMENT_PAGAMASTARDE_EOM_PREPARE_ORDER_STATUS_ID) {
            $sql_data_array = array('orders_id' => $order_id_from_pagantis,
            'orders_status_id' => MODULE_PAYMENT_PAGAMASTARDE_EOM_PREPARE_ORDER_STATUS_ID,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => '');

            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

            tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . (MODULE_PAYMENT_PAGAMASTARDE_EOM_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_PAGAMASTARDE_EOM_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', last_modified = now() where orders_id = '" . (int)$order_id_from_pagantis . "'");
          }

          $total_query = tep_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$order_id_from_pagantis . "' and class = 'ot_total' limit 1");
          $total = tep_db_fetch_array($total_query);

          $order_state_msg  = 'Paga+Tarde payment: '.$notification['data']['id'];

          $sql_data_array = array('orders_id' => $order_id_from_pagantis,
          'orders_status_id' => (MODULE_PAYMENT_PAGAMASTARDE_EOM_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_PAGAMASTARDE_EOM_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID),
          'date_added' => 'now()',
          'customer_notified' => '0',
          'comments' => $order_state_msg);

          tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

          // quitar referencia sesion
          tep_db_query("update " . TABLE_ORDERS . " set cc_owner = '' where orders_id = '" . (int)$order_id_from_pagantis . "'");

        }

        // hacemos la notificacion en la sesion del cliente
        // para enviar los emails de confirmacion
        if (function_exists('curl_exec')) {

          $url = trim( tep_href_link(FILENAME_CHECKOUT_PROCESS, 'osCsid='.$customer_session_id, 'SSL', false));
          $ch = curl_init();

          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPGET, true);
          curl_setopt($ch, CURLOPT_COOKIE, 'osCsid='.$customer_session_id);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HEADER, false);
          curl_setopt($ch, CURLOPT_TIMEOUT, 30);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

          $result = curl_exec($ch);

          curl_close($ch);
        }

      }

    }else{
       throw new Exception('Order not found');
    }

  }else{
    //upsell payment
    $order_id_from_pagantis = substr($notification['data']['order_id'], 0,strpos($notification['data']['order_id'],'-'));
    $order_query = tep_db_query("select * from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id_from_pagantis . "' ");
    $order = false;
    if (tep_db_num_rows($order_query) > 0) {

      $order = tep_db_fetch_array($order_query);
    }

    //add order history
    $order_state_msg  = 'Upsell Payment: '.number_format($notification['data']['amount']/100,2,'.','').'€, P+T id: "'.$notification['data']['id']. '", Order Id: '.$order_id_from_pagantis;
    $sql_data_array = array('orders_id' => $order_id_from_pagantis,
    'orders_status_id' => (MODULE_PAYMENT_PAGAMASTARDE_EOM_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_PAGAMASTARDE_EOM_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID),
    'date_added' => 'now()',
    'customer_notified' => '0',
    'comments' => $order_state_msg);

    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

    //add order amount
    //$total_query = tep_db_query("update " . TABLE_ORDERS_TOTAL . " set value = value + " . $notification['data']['amount']/100 . "  where orders_id = '" . (int)$order_id_from_pagantis . "' and class in ('ot_total','ot_subtotal') limit 1");
  }
}

if(isset($notification['event']) && $notification['event'] == 'refund.created')  {
  //refund payment
  if (strpos($notification['data']['order_id'], 'upsell')){
    $order_id_from_pagantis = substr($notification['data']['order_id'], 0,strpos($notification['data']['order_id'],'-'));
  }else{
    $order_id_from_pagantis = $notification['data']['order_id'];
  }

  $order_query = tep_db_query("select * from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id_from_pagantis . "' ");
  $order = false;
  if (tep_db_num_rows($order_query) > 0) {
    $order = tep_db_fetch_array($order_query);
  }

  $refund = end($notification['data']['refunds']);
  //add order history
  $order_state_msg  = 'Refund Payment: '.number_format($refund['amount']/100,2,'.','').'€, P+T id: "'.$refund['id']. '", Order Id: '.$order_id_from_pagantis;
  $sql_data_array = array('orders_id' => $order_id_from_pagantis,
  'orders_status_id' => (MODULE_PAYMENT_PAGAMASTARDE_EOM_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_PAGAMASTARDE_EOM_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID),
  'date_added' => 'now()',
  'customer_notified' => '0',
  'comments' => $order_state_msg);

  tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

  //add order amount
  //$total_query = tep_db_query("update " . TABLE_ORDERS_TOTAL . " set value = value - " . $refund['amount']/100 . "  where orders_id = '" . (int)$order_id_from_pagantis . "' and class in ('ot_total','ot_subtotal') limit 1");
}

require('includes/application_bottom.php');
?>
