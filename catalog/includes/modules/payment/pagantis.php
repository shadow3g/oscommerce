<?php

use Pagantis\ModuleUtils\Exception\OrderNotFoundException;
use Pagantis\OrdersApiClient\Model\Order\User\Address;
use Pagantis\ModuleUtils\Exception\UnknownException;

define('TABLE_PAGANTIS', 'pagantis');
define('TABLE_PAGANTIS_LOG', 'pagantis_log');
define('TABLE_PAGANTIS_CONFIG', 'pagantis_config');
define('TABLE_PAGANTIS_ORDERS', 'pagantis_orders');
define('TABLE_PAGANTIS_CONCURRENCY', 'pagantis_concurrency');
define('__ROOT__', dirname(dirname(__FILE__)));

class pagantis
{
    /** @var  String $is_guest */
    public $is_guest;

    /** @var Array $extraConfig */
    public $extraConfig;

    /** @var String $form_action_url */
    public $form_action_url;

    /** @var String $base_url */
    public $base_url;

    /** @var String $order_id */
    public $order_id;

    public $defaultConfigs = array('PMT_TITLE'=>'Instant Financing',
                                   'PMT_SIMULATOR_DISPLAY_TYPE'=>'pmtSDK.simulator.types.SIMPLE',
                                   'PMT_SIMULATOR_DISPLAY_SKIN'=>'pmtSDK.simulator.skins.BLUE',
                                   'PMT_SIMULATOR_DISPLAY_POSITION'=>'hookDisplayProductButtons',
                                   'PMT_SIMULATOR_START_INSTALLMENTS'=>3,
                                   'PMT_SIMULATOR_MAX_INSTALLMENTS'=>12,
                                   'PMT_SIMULATOR_CSS_POSITION_SELECTOR'=>'default',
                                   'PMT_SIMULATOR_DISPLAY_CSS_POSITION'=>'pmtSDK.simulator.positions.INNER',
                                   'PMT_SIMULATOR_CSS_PRICE_SELECTOR'=>'default',
                                   'PMT_SIMULATOR_CSS_QUANTITY_SELECTOR'=>'default',
                                   'PMT_FORM_DISPLAY_TYPE'=>0,
                                   'PMT_DISPLAY_MIN_AMOUNT'=>1,
                                   'PMT_URL_OK'=>'',
                                   'PMT_URL_KO'=>'',
                                   'PMT_TITLE_EXTRA' => 'Paga hasta en 12 cómodas cuotas con Paga+Tarde. Solicitud totalmente 
                            online y sin papeleos,¡y la respuesta es inmediata!'
    );

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
        }

        $this->enabled = ((MODULE_PAYMENT_PAGANTIS_STATUS == 'True') ? true : false);

        $this->getExtraConfig();

        if ((int)MODULE_PAYMENT_PAGANTIS_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PAGANTIS_ORDER_STATUS_ID;
        }

        if (strpos($_SERVER[REQUEST_URI], "checkout_confirmation.php")!==false && $_SESSION['order_id']) {
            if ($pmtOrderId = $this->getPmtOrderId($_SESSION['order_id'])) {
                $this->form_action_url = "https://form.pagamastarde.com/orders/$pmtOrderId";
            }
        }
        /*if (is_object($order)) {
            $this->update_status();
        }*/
        
        $this->base_url = dirname(
            sprintf(
                "%s://%s%s%s",
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
                $_SERVER['SERVER_NAME'],
                isset($_SERVER['SERVER_PORT']) ? ":".$_SERVER['SERVER_PORT'] : '',
                $_SERVER['REQUEST_URI']
            )
        );
        $this->form_action_url = $this->base_url . '/ext/modules/payment/pagantis/bypass.php';
    }

    /***************
     *
     * CLASS METHODS
     *
     **************/

    /**
    * Here you can implement using payment zones (refer to standard PayPal module as reference)
    */
    public function update_status()
    {
        global $order, $db;

        if (strpos($_SERVER[REQUEST_URI], "checkout_process.php") > 0) {
            if ($_POST) {
                die('aqui hemos llegado por notificación');
            } else {
                echo "pasamos de largo pq es un GET";
            }
        }

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
    * - el usuario decide realizar algun cambio en su compra antes de pasar a pagantis
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
    * - con el estado predefinido para el modulo pagantis
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
     */
    public function process_button()
    {
        try {
            include_once('./ext/modules/payment/pagantis/vendor/autoload.php');
            global $order, $customer_id, $sendto, $billto, $cart, $languages_id, $currency, $currencies, $shipping, $payment, $comments, $customer_default_address_id, $cartID;
            $global_vars = array();
            $global_vars['customer_id'] = serialize($customer_id);
            $global_vars['sendTo'] = serialize($sendto);
            $global_vars['billTo'] = serialize($billto);
            $global_vars['cart'] = serialize($cart);
            $global_vars['languages_id'] = serialize($languages_id);
            $global_vars['currency'] = serialize($currency);
            $global_vars['currencies'] = serialize($currencies);
            $global_vars['shipping'] = serialize($shipping);
            $global_vars['payment'] = serialize($payment);
            $global_vars['comments'] = serialize($comments);
            $global_vars['$customer_default_address_id'] = serialize($customer_default_address_id);
            $global_vars['cartId'] = serialize($cartID)
print_R($GLOBALS);die;
            if (!isset($order)) {
                throw new UnknownException("Order not found");
            }

            $id_hash = time().serialize($order->products).''.serialize($order->customer).''.serialize($order->delivery);
            $this->order_id = md5($id_hash);
            $_SESSION['order_id'] = $this->order_id;
            $sql = sprintf("insert into " . TABLE_PAGANTIS . " (order_id) values ('%s')", $this->order_id);
            tep_db_query($sql);

            $userAddress = new Address();
            $userAddress
                ->setZipCode($order->billing['postcode'])
                ->setFullName($order->billing['firstname'].' '.$order->billing['lastname'])
                ->setCountryCode('ES')
                ->setCity($order->billing['city'])
                ->setAddress($order->billing['street_address'])
                ->setFixPhone($order->customer['telephone'])
                ->setMobilePhone($order->customer['telephone']);

            $orderBillingAddress = $userAddress;

            $orderShippingAddress = new Address();
            $orderShippingAddress
                ->setZipCode($order->delivery['postcode'])
                ->setFullName($order->billing['firstname'].' '.$order->billing['lastname'])
                ->setCountryCode('ES')
                ->setCity($order->delivery['city'])
                ->setAddress($order->delivery['street_address'])
                ->setFixPhone($order->customer['telephone'])
                ->setMobilePhone($order->customer['telephone']);

            $orderUser = new \Pagantis\OrdersApiClient\Model\Order\User();
            $orderUser
                ->setAddress($userAddress)
                ->setFullName($order->billing['firstname'].' '.$order->billing['lastname'])
                ->setBillingAddress($orderBillingAddress)
                ->setEmail($order->customer['email_address'])
                ->setFixPhone($order->customer['telephone'])
                ->setMobilePhone($order->customer['telephone'])
                ->setShippingAddress($orderShippingAddress);

            $previousOrders = $this->getOrders();
            foreach ((array)$previousOrders as $previousOrder) {
                $orderHistory = new \Pagantis\OrdersApiClient\Model\Order\User\OrderHistory();
                $orderElement = wc_get_order($previousOrder);
                $orderCreated = $orderElement->get_date_created();
                $orderHistory
                    ->setAmount(intval(100 * $orderElement->get_total()))
                    ->setDate(new \DateTime($orderCreated->date('Y-m-d H:i:s')))
                ;
                $orderUser->addOrderHistory($orderHistory);
            }

            $details      = new \Pagantis\OrdersApiClient\Model\Order\ShoppingCart\Details();
            $shippingCost = number_format($order->info['shipping_cost'], 2, '.', '');
            $details->setShippingCost(intval(strval(100 * $shippingCost)));
            foreach ($order->products as $item) {
                $product = new \Pagantis\OrdersApiClient\Model\Order\ShoppingCart\Details\Product();
                $product
                    ->setAmount(intval(100 * number_format(($item['final_price'] * $item['qty']), 2)))
                    ->setQuantity(intval($item['qty']))
                    ->setDescription($item['name']);
                $details->addProduct($product);
            }

            $orderShoppingCart = new \Pagantis\OrdersApiClient\Model\Order\ShoppingCart();
            $orderShoppingCart
                ->setDetails($details)
                ->setOrderReference($this->order_id)
                ->setPromotedAmount(0)
                ->setTotalAmount(intval($order->info['total'] * 100));

            $callback_url = $this->base_url.'/ext/modules/payment/pagantis/notify.php';
            $checkoutProcessUrl = htmlspecialchars_decode(
                tep_href_link(FILENAME_CHECKOUT_PROCESS, "order_id=$this->order_id", 'SSL', true, false)
            );
            $cancelUrl              = trim(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL', false));
            $orderConfigurationUrls = new \Pagantis\OrdersApiClient\Model\Order\Configuration\Urls();
            $orderConfigurationUrls
                ->setCancel($cancelUrl)
                ->setKo($checkoutProcessUrl)
                ->setAuthorizedNotificationCallback($callback_url)
                ->setRejectedNotificationCallback($callback_url)
                ->setOk($checkoutProcessUrl);


            $orderChannel = new \Pagantis\OrdersApiClient\Model\Order\Configuration\Channel();
            $orderChannel
                ->setAssistedSale(false)
                ->setType(\Pagantis\OrdersApiClient\Model\Order\Configuration\Channel::ONLINE);
            $orderConfiguration = new \Pagantis\OrdersApiClient\Model\Order\Configuration();
            $orderConfiguration
                ->setChannel($orderChannel)
                ->setUrls($orderConfigurationUrls);

            $metadataOrder = new \Pagantis\OrdersApiClient\Model\Order\Metadata();
            $metadata      = array(
                'oscommerce' => PROJECT_VERSION,
                'pagantis'   => $this->version,
                'php'        => phpversion()
            );
            foreach ($metadata as $key => $metadatum) {
                $metadataOrder->addMetadata($key, $metadatum);
            }
            $orderApiClient = new \Pagantis\OrdersApiClient\Model\Order();
            $orderApiClient
                ->setConfiguration($orderConfiguration)
                ->setMetadata($metadataOrder)
                ->setShoppingCart($orderShoppingCart)
                ->setUser($orderUser);

            $publicKey     = trim(MODULE_PAYMENT_PAGANTIS_PK);
            $secretKey     = trim(MODULE_PAYMENT_PAGANTIS_SK);
            $orderClient   = new \Pagantis\OrdersApiClient\Client($publicKey, $secretKey);
            $pagantisOrder = $orderClient->createOrder($orderApiClient);
            if ($pagantisOrder instanceof \Pagantis\OrdersApiClient\Model\Order) {
                $url = $pagantisOrder->getActionUrls()->getForm();
                $this->insertRow($this->order_id, serialize($global_vars));
                die($this->order_id);
            } else {
                throw new OrderNotFoundException();
            }

            if ($url == "") {
                throw new UnknownException(_("No ha sido posible obtener una respuesta de Pagantis"));
            } else { //if ($this->extraConfig['PAGANTIS_FORM_DISPLAY_TYPE'] == '0') {
                $output = "\n";
                $output.= tep_draw_hidden_field("formUrl", $url) . "\n";
                $output.= tep_draw_hidden_field("cancelUrl", $cancelUrl) . "\n";
                return $output;
            } /*else {
                $template_fields = array(
                    'url'         => $url,
                    'checkoutUrl' => $cancelUrl
                );
                wc_get_template('iframe.php', $template_fields, '', $this->template_path); //TODO
            }*/ //
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            exit;
            tep_redirect($cancelUrl);
            return;
        }
    }

    /**
     *
     */
    public function before_process()
    {
        if (!$_POST) {
            die('aqui hemos llegado por order');
        } else {
            echo "pasamos de largo pq es un POST y ya debe estar verificado";
        }
        exit;
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
            ('".'Pagantis.  Transaction ID: ' .$this->notification['data']['id']."', ".$insert_id.", '".$this->order_status."', -1, now() )";
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
        $this->install_pagantis_tables();
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
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Pagantis Module', 'MODULE_PAYMENT_PAGANTIS_STATUS', 'True', '', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Public Key', 'MODULE_PAYMENT_PAGANTIS_PK', '', 'MANDATORY. You can get in your pagantis profile', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Secret Key', 'MODULE_PAYMENT_PAGANTIS_SK', '', 'MANDATORY. You can get in your pagantis profile', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Include simulator', 'MODULE_PAYMENT_PAGANTIS_SIMULATOR', 'True', 'Do you want to include simulator in product page?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $this->install_pagantis_tables();
    }

    /**
     * Create the neccesary tables for the module
     */
    private function install_pagantis_tables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PAGANTIS . " (
            `id` int(11) NOT NULL auto_increment,
            `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `order_id` varchar(150) NOT NULL,
            `json` TEXT,
            PRIMARY KEY (id),
            KEY (order_id))";
        tep_db_query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PAGANTIS_LOG . " ( 
                          id int NOT NULL AUTO_INCREMENT, 
                          log text NOT NULL, 
                          createdAt timestamp DEFAULT CURRENT_TIMESTAMP, 
                          UNIQUE KEY id (id))";
        tep_db_query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PAGANTIS_CONFIG . " (
                            id int NOT NULL AUTO_INCREMENT, 
                            config varchar(60) NOT NULL, 
                            value varchar(100) NOT NULL, 
                            UNIQUE KEY id(id))";
        tep_db_query($sql);
        foreach ((array)$this->extraConfig as $configKey => $configValue) {
            $query = "INSERT INTO " . TABLE_PAGANTIS_CONFIG . " (config, value) values ($configKey, $configValue)";
            tep_db_query($query);
        }

        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PAGANTIS_ORDERS . " (
                            id int NOT NULL AUTO_INCREMENT, 
                            os_order_id varchar(50) NOT NULL, 
                            pmt_order_id varchar(50) NOT NULL, 
                            UNIQUE KEY id(id))";
        tep_db_query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PAGANTIS_CONCURRENCY . " (
                            id int NOT NULL,
                            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE KEY id(id))";
        tep_db_query($sql);
    }

    /*
     * Standard functionality to uninstall the module.
     */
    public function remove()
    {
        $checkTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS."'");
        if (tep_db_num_rows($checkTable) > 0) {
            tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
            tep_db_query("drop table " . TABLE_PAGANTIS);
        }

        $checkTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS_LOG."'");
        if (tep_db_num_rows($checkTable) > 0) {
            tep_db_query("drop table " . TABLE_PAGANTIS_LOG);
        }

        $checkTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS_CONFIG."'");
        if (tep_db_num_rows($checkTable) > 0) {
            tep_db_query("drop table " . TABLE_PAGANTIS_CONFIG);
        }

        $checkTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS_ORDERS."'");
        if (tep_db_num_rows($checkTable) > 0) {
            tep_db_query("drop table " . TABLE_PAGANTIS_ORDERS);
        }

        $checkTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS_CONCURRENCY."'");
        if (tep_db_num_rows($checkTable) > 0) {
            tep_db_query("drop table " . TABLE_PAGANTIS_CONCURRENCY);
        }
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
           'MODULE_PAYMENT_PAGANTIS_SK',
           'MODULE_PAYMENT_PAGANTIS_SIMULATOR');
    }

    /**
     * @return array
     */
    private function getOrders()
    {
        // extra parameters for logged users
        $sign_up = '';
        $dob = '';
        $order_total = 0;
        $order_count = 0;
        $this->is_guest = 'true';
        $result = array();
        if (trim($_SESSION['customer_id']) != '') {
            $this->is_guest = 'false';
            /*$sql = sprintf(
                "SELECT customers_info_date_account_created, customers_dob, customers_gender
                FROM %s
                JOIN %s ON customers_info.customers_info_id = customers.customers_id
                Where  customers.customers_id = %d",
                TABLE_CUSTOMERS,
                TABLE_CUSTOMERS_INFO,
                $_SESSION['customer_id']
            );
            $check_query = tep_db_query($sql);
            while ($check = tep_db_fetch_array($check_query)) {
                $sign_up = substr($check['customers_info_date_account_created'], 0, 10);
                $dob = substr($check['customers_dob'], 0, 10);
                $gender = $check['customers_gender'] == 'm' ? 'male' : 'female';
            }*/
            $sql = sprintf(
                "select orders_total.value from %s join %s on orders_status.orders_status_id = orders.orders_status
            join %s on orders.orders_id = orders_total.orders_id and orders_total.class = 'ot_total'
            where customers_id=%d and orders_status.orders_status_name in ('Processing','Delivered')
            order by orders.orders_id",
                TABLE_ORDERS_STATUS,
                TABLE_ORDERS,
                TABLE_ORDERS_TOTAL,
                $_SESSION['customer_id']
            );
            $check_query = tep_db_query($sql);
            $result = tep_db_fetch_array($check_query);
        }
        return $result;
    }

    /**
     * @param $orderId
     * @param $pmtOrderId
     *
     * @throws Exception
     */
    private function insertRow($orderId, $pmtOrderId)
    {
        $query = "select * from ". TABLE_PAGANTIS_ORDERS ." where os_order_id='$orderId'";
        $resultsSelect = tep_db_query($query);
        $countResults = tep_db_num_rows($resultsSelect);
        if ($countResults == 0) {
            $query = "INSERT INTO " . TABLE_PAGANTIS_ORDERS ."(os_order_id, pmt_order_id) values ('$orderId', '$pmtOrderId')";
        } else {
            $query = "UPDATE " . TABLE_PAGANTIS_ORDERS . " set pmt_order_id='$pmtOrderId' where os_order_id='$orderId'";
        }
        tep_db_query($query);
    }

    private function getExtraConfig()
    {
        $checkTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS."'");
        $response = array();
        if (tep_db_num_rows($checkTable) > 0) {
            $query       = "select * from ".TABLE_PAGANTIS_CONFIG;
            $result      = tep_db_query($query);
            $resultArray = tep_db_fetch_array($result);
            $response    = array();
            foreach ((array)$resultArray as $key => $value) {
                $response[$key] = $value;
            }
        }

        return $response;
    }

    private function getPmtOrderId($osOrderId)
    {
        $result = '';
        $query = "select pmt_order_id from ". TABLE_PAGANTIS_ORDERS ." where os_order_id='$osOrderId'";
        $resultsSelect = tep_db_query($query);
        while ($orderRow = tep_db_fetch_array($resultsSelect)) {
            $result = $orderRow['pmt_order_id'];
        }
        return $result;
    }
}
