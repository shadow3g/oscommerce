<?php
/**
 * PagaMasTarde payment module for oscommerce
 *
 * @package     PagaMasTarde
 * @author      Albert Fatsini <afatsini@digitalorigin.com>
 * @copyright   Copyright (c) 2017  Paga+Tarde (http://www.pagamastarde.com)
 *
 * @license     Released under the GNU General Public License
 *
 */

define('TABLE_PAGAMASTARDE', 'pagamastarde');
chdir('../../../../');
require('includes/application_top.php');

$json = file_get_contents('php://input');
$notification = json_decode($json, true);
if(isset($notification['event']) && $notification['event'] != 'charge.created')  {
  die('Not processing notification');
}

if (MODULE_PAYMENT_PAGAMASTARDE_TESTMODE == 'Test'){
  $secret_key = MODULE_PAYMENT_PAGAMASTARDE_TSK;
  $public_key = MODULE_PAYMENT_PAGAMASTARDE_TK;
} else {
  $secret_key = MODULE_PAYMENT_PAGAMASTARDE_PSK;
  $public_key = MODULE_PAYMENT_PAGAMASTARDE_PK;
}
  $signature_check = sha1($secret_key.$notification['account_id'].$notification['api_version'].$notification['event'].$notification['data']['id']);
$signature_check_sha512 = hash('sha512',$pagamastarde_secret.$notification['account_id'].$notification['api_version'].$notification['event'].$notification['data']['id']);
if ($signature_check != $notification['signature'] && $signature_check_sha512 != $notification['signature'] ){
  die( 'Fallo en el proceso de pago. Su pedido ha sido cancelado.' );
  exit;
} else {
  $sql ="update " . TABLE_PAGAMASTARDE . " set json = '".addslashes($json)."' where order_id = '".$notification['data']['order_id']."'";
  tep_db_query($sql);
  echo 'OK';
}
?>
