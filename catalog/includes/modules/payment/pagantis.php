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

use Pagantis\ModuleUtils\Exception\OrderNotFoundException;
use Pagantis\OrdersApiClient\Model\Order\User\Address;
use Pagantis\ModuleUtils\Exception\UnknownException;

define('TABLE_PAGANTIS', 'pagantis');
define('__ROOT__', dirname(dirname(__FILE__)));

class pagantis
{
    /**
    * Constructor
    */
    public function __construct()
    {
        global $order;
        $this->version = '8.0.0';
        $this->code = 'pagantis';
        if (strpos($_SERVER[REQUEST_URI], "checkout_payment.php") <= 0) {
            $this->title = MODULE_PAYMENT_PAGANTIS_TEXT_ADMIN_TITLE; // Payment module title in Admin
        } else {
            $this->title = MODULE_PAYMENT_PAGANTIS_TEXT_CATALOG_TITLE; // Payment module title in Catalog
            if ($_SESSION['currency'] != 'EUR') {
                return false;
            }
        }
        $this->description = MODULE_PAYMENT_PAGANTIS_TEXT_DESCRIPTION;
        $this->enabled = ((MODULE_PAYMENT_PAGANTIS_STATUS == 'True') ? true : false);
        $this->sort_order = MODULE_PAYMENT_PAGANTIS_SORT_ORDER;

        if ((int)MODULE_PAYMENT_PAGANTIS_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PAGANTIS_ORDER_STATUS_ID;
        }
        if (is_object($order)) {
            $this->update_status();
        }

        $this->form_action_url = 'https://pmt.pagantis.com/v1/installments';
        $this->version = '2.2';
    }

    // class methods
    /**
    * Here you can implement using payment zones (refer to standard PayPal module as reference)
    */
    public function update_status()
    {
        global $order, $db;
        if ($this->enabled && (int)MODULE_PAYMENT_PAGANTIS_ZONE > 0 && isset($order->billing['country']['id'])) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAGANTIS_ZONE . "' and zone_country_id = '" . (int)$order->billing['country']['id'] . "' order by zone_id");
            while ($check = tep_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    /*
    * Here you may define client side javascript that will verify any input fields you use in the payment method
    * selection page. Refer to standard cc module as reference (cc.php).
    */
    public function javascript_validation()
    {
        return false;
    }

    /*
    * Llamada cuando el usuario esta en la pantalla de eleccion de tipo de pago
     * This function outputs the payment method title/text and if required, the input fields.
    *
    * Si hay un pedido generado previamente y no confirmado, se borra
    * Caso de uso:
    * - el usuario llega a la pantalla de confirmacion
    * - se genera el pedido (pero no se genera entrada en orders_status_history)
    * - el usuario decide realizar algun cambio en su compra antes de pasar a PagaMasTarde
    * - entra de nuevo en la pantalla de seleccion de tipo de pago (puede elegir otra forma de pago)
    * - se comprueba que no exista el pedido generado anteriormente
    * - se borra el pedido que se habia generado inicialmente. Ya no es valido
    *
    */
    public function selection()
    {
        return array('id' => $this->code, 'module' => $this->title);
    }

    /*
    * Use this function implement any checks of any conditions after payment method has been selected. You most probably
    *  don't need to implement anything here.
    */
    public function pre_confirmation_check()
    {
        return false;
    }

    /*
     * Implement any checks or processing on the order information before proceeding to payment confirmation. You most
    probably don't need to implement anything here.
    * Llamada cuando el usuario entra en la pantalla de confirmacion
    *
    * Se genera el pedido:
    * - con el estado predefinido para el modulo PagaMasTarde
    * - sin notificacion a cliente ni administrador
    * - no se borra el carrito asociado al pedido
    *
    */
    public function confirmation()
    {
        return false;
    }

    /**
    * Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
    * This sends the data to the payment gateway for processing.
    * (These are hidden fields on the checkout confirmation page)
    *
    * @return string
    */
    public function process_button()
    {
        require_once(__ROOT__.'/vendor/autoload.php');
        global $order;

        if (!isset($order)) {
            throw new UnknownException(_("Order not found"));
        }

        $userAddress = new Address();
        $userAddress
            ->setZipCode($order->billing['postcode'])
            ->setFullName($order->billing['firstname'] . ' ' . $order->billing['lastname'])
            ->setCountryCode('ES')
            ->setCity($order->billing['city'])
            ->setAddress($order->billing['street_address'])
        ;

        $orderBillingAddress = $userAddress;

        $orderShippingAddress = new Address();
        $orderShippingAddress
            ->setZipCode($order->delivery['postcode'])
            ->setFullName($order->billing['firstname'] . ' ' . $order->billing['lastname'])
            ->setCountryCode('ES')
            ->setCity($order->delivery['city'])
            ->setAddress($order->delivery['street_address'])
            ->setFixPhone($order->customer['telephone'])
            ->setMobilePhone($order->customer['telephone'])
        ;

        $orderUser = new \Pagantis\OrdersApiClient\Model\Order\User();
        $orderUser
            ->setAddress($userAddress)
            ->setFullName($order->billing['firstname'] . ' ' . $order->billing['lastname'])
            ->setBillingAddress($orderBillingAddress)
            ->setEmail($order->customer['email_address'])
            ->setFixPhone($order->customer['telephone'])
            ->setMobilePhone($order->customer['telephone'])
            ->setShippingAddress($orderShippingAddress)
        ;

        /* TODO
        foreach ($previousOrders as $previousOrder) {
            $orderHistory = new \Pagantis\OrdersApiClient\Model\Order\User\OrderHistory();
            $orderElement = wc_get_order($previousOrder);
            $orderCreated = $orderElement->get_date_created();
            $orderHistory
                ->setAmount(intval(100 * $orderElement->get_total()))
                ->setDate(new \DateTime($orderCreated->date('Y-m-d H:i:s')))
            ;
            $orderUser->addOrderHistory($orderHistory);
        }*/

        $details = new \Pagantis\OrdersApiClient\Model\Order\ShoppingCart\Details();
        $shippingCost = number_format($order->info['shipping_cost'], 2, '.', '');
        $details->setShippingCost(intval(strval(100 * $shippingCost)));
        foreach ($order->products as $item) {
            $product = new \Pagantis\OrdersApiClient\Model\Order\ShoppingCart\Details\Product();
            $product
                ->setAmount(number_format($product['final_price'] * $product['qty'], 2, '.', ''))
                ->setQuantity($item['qty'])
                ->setDescription($item['name']);
            $details->addProduct($product);
        }

        $orderShoppingCart = new \Pagantis\OrdersApiClient\Model\Order\ShoppingCart();
        $orderShoppingCart
            ->setDetails($details)
            ->setOrderReference($order->info['id'])
            ->setPromotedAmount(0)
            ->setTotalAmount(number_format($order->info['total'] * 100, 0, '', ''))
        ;

        $base_url = dirname(
            sprintf(
                "%s://%s%s",
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
                $_SERVER['SERVER_NAME'],
                $_SERVER['REQUEST_URI']
            )
        );
        $callback_url = $base_url . '/ext/modules/payment/pagamastarde/callback.php';
        $okUrl = htmlspecialchars_decode(tep_href_link(FILENAME_CHECKOUT_PROCESS, 'action=confirm', 'SSL', true, false));
        $koUrl = trim(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL', false));
        $cancelUrl = trim(tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', false));
        $orderConfigurationUrls = new \Pagantis\OrdersApiClient\Model\Order\Configuration\Urls();
        $orderConfigurationUrls
            ->setCancel($cancelUrl)
            ->setKo($koUrl)
            ->setAuthorizedNotificationCallback($callback_url)
            ->setRejectedNotificationCallback($callback_url)
            ->setOk($okUrl)
        ;

        $orderChannel = new \Pagantis\OrdersApiClient\Model\Order\Configuration\Channel();
        $orderChannel
            ->setAssistedSale(false)
            ->setType(\Pagantis\OrdersApiClient\Model\Order\Configuration\Channel::ONLINE)
        ;
        $orderConfiguration = new \Pagantis\OrdersApiClient\Model\Order\Configuration();
        $orderConfiguration
            ->setChannel($orderChannel)
            ->setUrls($orderConfigurationUrls)
        ;

        $metadataOrder = new \Pagantis\OrdersApiClient\Model\Order\Metadata();
        $metadata = array(
            'oscommerce' => PROJECT_VERSION,
            'pagantis'         => $this->version,
            'php'         => phpversion()
        );
        foreach ($metadata as $key => $metadatum) {
            $metadataOrder->addMetadata($key, $metadatum);
        }
        $orderApiClient = new \Pagantis\OrdersApiClient\Model\Order();
        $orderApiClient
            ->setConfiguration($orderConfiguration)
            ->setMetadata($metadataOrder)
            ->setShoppingCart($orderShoppingCart)
            ->setUser($orderUser)
        ;

        $publicKey = MODULE_PAYMENT_PAGANTIS_PK;
        $secretKey = MODULE_PAYMENT_PAGANTIS_SK;
        $orderClient = new \Pagantis\OrdersApiClient\Client($publicKey, $secretKey);
        $pagantisOrder = $orderClient->createOrder($orderApiClient);
        if ($pagantisOrder instanceof \Pagantis\OrdersApiClient\Model\Order) {
            $url = $pagantisOrder->getActionUrls()->getForm();
            $this->insertRow($order->get_id(), $pagantisOrder->getId()); //TODO
        } else {
            throw new OrderNotFoundException();
        }

        if ($url=="") {
            throw new UnknownException(_("No ha sido posible obtener una respuesta de Pagantis"));
        } elseif (getenv('PAGANTIS_FORM_DISPLAY_TYPE')=='0') { //TODO
            tep_redirect($url);
            return;
        } else {
            $template_fields = array(
                'url' => $url,
                'checkoutUrl'   => $cancelUrl
            );
            wc_get_template('iframe.php', $template_fields, '', $this->template_path); //TODO
        }
    }



    public function before_process()
    {
        global $messageStack, $order, $db;
        $this->order_id = $_SESSION['order_id'];
        $sql = sprintf("select json from %s where order_id='%s' order by id desc limit 1", TABLE_PAGANTIS, $this->order_id);
        $check_query = tep_db_query($sql);
        while ($check = tep_db_fetch_array($check_query)) {
            $this->notification = json_decode(stripcslashes($check['json']), true);
        }

        $secret_key = MODULE_PAYMENT_PAGANTIS_PSK;
        $public_key = MODULE_PAYMENT_PAGANTIS_PK;
        $notififcation_check = true;
        $signature_check = sha1($secret_key.
        $this->notification['account_id'].
        $this->notification['api_version'].
        $this->notification['event'].
        $this->notification['data']['id']);
        $signature_check_sha512 = hash(
            'sha512',
            $secret_key.
            $this->notification['account_id'].
            $this->notification['api_version'].
            $this->notification['event'].
            $this->notification['data']['id']
        );
        if ($signature_check != $this->notification['signature'] && $signature_check_sha512 != $this->notification['signature']) {
            $notififcation_check = false;
        }

        if ($notififcation_check && $this->notification['event'] == 'charge.created') {
            //$this->notify('NOTIFY_PAYMENT_AUTHNETSIM_POSTSUBMIT_HOOK', $this->notification);
            $this->auth_code = 'paga+tarde';
            $this->transaction_id = $this->notification['data']['id'];
            return;
        } else {
            $messageStack->add_session('checkout_payment', MODULE_PAYMENT_PAGANTIS_TEXT_DECLINED_MESSAGE, 'error');
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }
    }

    /**
    * Post-processing activities
    *
    * @return boolean
    */
    public function after_process()
    {
        global $insert_id, $order, $currencies;
        $this->order_id = $_SESSION['order_id'];
        $sql = sprintf("select json from %s where order_id='%s' order by id desc limit 1", TABLE_PAGANTIS, $this->order_id);
        $check_query = tep_db_query($sql);
        while ($check = tep_db_fetch_array($check_query)) {
            $this->notification = json_decode(stripcslashes($check['json']), true);
        }
        if (MODULE_PAYMENT_PAGANTIS_TESTMODE == 'Test') {
            $secret_key = MODULE_PAYMENT_PAGANTIS_TSK;
            $public_key = MODULE_PAYMENT_PAGANTIS_TK;
        } else {
            $secret_key = MODULE_PAYMENT_PAGANTIS_PSK;
            $public_key = MODULE_PAYMENT_PAGANTIS_PK;
        }
        $notififcation_check = true;
        $signature_check = sha1($secret_key.
        $this->notification['account_id'].
        $this->notification['api_version'].
        $this->notification['event'].
        $this->notification['data']['id']);
        $signature_check_sha512 = hash(
            'sha512',
            $secret_key.
            $this->notification['account_id'].
            $this->notification['api_version'].
            $this->notification['event'].
            $this->notification['data']['id']
        );
        if ($signature_check != $this->notification['signature'] && $signature_check_sha512 != $this->notification['signature']) {
            $notififcation_check = false;
        }
        //$this->notify('NOTIFY_PAYMENT_AUTHNETSIM_POSTPROCESS_HOOK');
        if ($notififcation_check && $this->notification['event'] == 'charge.created') {
            $sql = "insert into " . TABLE_ORDERS_STATUS_HISTORY . " (comments, orders_id, orders_status_id, customer_notified, date_added) values
            ('".'Paga+tarde.  Transaction ID: ' .$this->notification['data']['id']."', ".$insert_id.", '".$this->order_status."', -1, now() )";
            tep_db_query($sql);
        }
        unset($_SESSION['order_id']);
        return false;
    }

    public function output_error()
    {
        return false;
    }

    public function check()
    {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAGANTIS_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        $this->_check_install_pmt_table();
        return $this->_check;
    }

    /*
     * This is where you define module's configurations (displayed in admin).
     */
    public function install()
    {
        global $messageStack;
        if (defined('MODULE_PAYMENT_PAGANTIS_STATUS')) {
            $messageStack->add_session('Pagantis already installed.', 'error');
            tep_redirect(tep_href_link(FILENAME_MODULES, 'set=payment&module=pagantis', 'NONSSL'));
            return 'failed';
        }
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Paga+Tarde Module', 'MODULE_PAYMENT_PAGANTIS_STATUS', 'True', '', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Public Key', 'MODULE_PAYMENT_PAGANTIS_PK', 'pk_XXXX', 'Public key', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Secret Key', 'MODULE_PAYMENT_PAGANTIS_SK', 'secret', 'Secret key', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Include Widget', 'MODULE_PAYMENT_PAGANTIS_SIMULATOR', 'False', 'Do you want to include the Paga+Tarde widget in the checkout page?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $this->_check_install_pmt_table();
    }

    public function _check_install_pmt_table()
    {
        $CheckTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS."'");
        if (tep_db_num_rows($CheckTable) <= 0) {
            $sql = "CREATE TABLE " . TABLE_PAGANTIS . " (
            `id` int(11) NOT NULL auto_increment,
            `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `order_id` varchar(150) NOT NULL,
            `json` TEXT,
            PRIMARY KEY (id),
            KEY (order_id))";
            tep_db_query($sql);
        }
    }

    /*
     * Standard functionality to uninstall the module.
     */
    public function remove()
    {
        $CheckTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS."'");
        if (tep_db_num_rows($CheckTable) > 0) {
            tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        }
        tep_db_query("drop table " . TABLE_PAGANTIS);
    }

    /**
    * Internal list of configuration keys used for configuration of the module
    *
    * @return array
    */
    public function keys()
    {
        return array('MODULE_PAYMENT_PAGANTIS_STATUS',
           'MODULE_PAYMENT_PAGANTIS_PK',
           'MODULE_PAYMENT_PAGANTIS_PSK',
           'MODULE_PAYMENT_PAGANTIS_SIMULATOR');
    }
}
