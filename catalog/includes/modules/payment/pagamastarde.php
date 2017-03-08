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

class pagamastarde
{
    /**
    * Constructor
    */
    public function __construct()
    {
        global $order;

        if ($_SESSION['currency'] != 'EUR') {
            return false;
        }

        $this->code = 'pagamastarde';
        if (strpos($_SERVER[REQUEST_URI], "checkout_payment.php") <= 0) {
            $this->title = MODULE_PAYMENT_PAGAMASTARDE_TEXT_ADMIN_TITLE; // Payment module title in Admin
        } else {
            $this->title = MODULE_PAYMENT_PAGAMASTARDE_TEXT_CATALOG_TITLE; // Payment module title in Catalog
        }
        $this->description = MODULE_PAYMENT_PAGAMASTARDE_TEXT_DESCRIPTION;
        $this->enabled = ((MODULE_PAYMENT_PAGAMASTARDE_STATUS == 'True') ? true : false);
        $this->sort_order = MODULE_PAYMENT_PAGAMASTARDE_SORT_ORDER;

        if ((int)MODULE_PAYMENT_PAGAMASTARDE_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PAGAMASTARDE_ORDER_STATUS_ID;
        }
        if (is_object($order)) {
            $this->update_status();
        }

        $this->form_action_url = 'https://pmt.pagantis.com/v1/installments';
        $this->version = '2.1';
    }

    // class methods
    /**
    * Calculate zone matches and flag settings to determine whether this module should display to customers or not
    */
    public function update_status()
    {
        global $order, $db;
        if ($this->enabled && (int)MODULE_PAYMENT_PAGAMASTARDE_ZONE > 0 && isset($order->billing['country']['id'])) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAGAMASTARDE_ZONE . "' and zone_country_id = '" . (int)$order->billing['country']['id'] . "' order by zone_id");
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
    *  validacion inicial
    */
    public function javascript_validation()
    {
        return false;
    }

    /*
    * Llamada cuando el usuario esta en la pantalla de eleccion de tipo de pago
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
        return array('id' => $this->code,
        'module' => $this->title);
    }

    /*
    * Validacion antes de pasar a pantalla confirmacion
    */
    public function pre_confirmation_check()
    {
        return false;
    }

    /*
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
        global $order;
        $this->order_id = md5(serialize($order->products) .''. serialize($order->customer) .''. serialize($order->delivery));
        $_SESSION['order_id'] = $this->order_id;
        $sql = sprintf("insert into " . TABLE_PAGAMASTARDE . " (order_id) values ('%s')", $this->order_id);
        tep_db_query($sql);
        $base_url = dirname(
            sprintf(
                "%s://%s%s",
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
                $_SERVER['SERVER_NAME'],
                $_SERVER['REQUEST_URI']
            )
        );
        $callback_url = $base_url . '/ext/modules/payment/pagamastarde/callback.php';
        $pagamastarde_ok_url = htmlspecialchars_decode(tep_href_link(FILENAME_CHECKOUT_PROCESS, 'action=confirm', 'SSL', true, false));
        $pagamastarde_nok_url = trim(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL', false));
        $cancelled_url = trim(tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', false));
        $amount = number_format($order->info['total'] * 100, 0, '', '');
        $currency = $order->info['currency'];
        if (MODULE_PAYMENT_PAGAMASTARDE_DISCOUNT == 'False') {
            $discount = 'false';
        } else {
            $discount = 'true';
        }
        if (MODULE_PAYMENT_PAGAMASTARDE_TESTMODE == 'Test') {
            $secret_key = MODULE_PAYMENT_PAGAMASTARDE_TSK;
            $public_key = MODULE_PAYMENT_PAGAMASTARDE_TK;
        } else {
            $secret_key = MODULE_PAYMENT_PAGAMASTARDE_PSK;
            $public_key = MODULE_PAYMENT_PAGAMASTARDE_PK;
        }
        $message = $secret_key.
        $public_key.
        $this->order_id.
        $amount.
        $currency.
        $pagamastarde_ok_url.
        $pagamastarde_nok_url.
        $callback_url.
        $discount.
        $cancelled_url;
        $signature = hash('sha512', $message);

        // extra parameters for logged users
        $sign_up = '';
        $dob = '';
        $order_total = 0;
        $order_count = 0;
        $is_guest = 'true';
        if (trim($_SESSION['customer_id']) != '') {
            $is_guest = 'false';
            $sql = sprintf(
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
            }

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

            while ($check = tep_db_fetch_array($check_query)) {
                $order_total += $check['value'];
                $order_count += 1;
            }
        }
        $billing_dob = '';
        if ($order->billing['firstname'] == $order->customer['firstname'] &&
            $order->billing['lastname'] == $order->customer['lastname'] ) {
              $billing_dob = $dob;
        }

        $submit_data = array(
          'account_id' => $public_key,
          'currency' => $currency,
          'ok_url' => $pagamastarde_ok_url,
          'nok_url' => $pagamastarde_nok_url,
          'cancelled_url' => $cancelled_url,
          'callback_url' => $callback_url,
          'order_id' => $this->order_id,
          'amount' => $amount,
          'signature' => $signature,
          'discount[full]' => $discount,
          'dob' => $billing_dob,

          'full_name' =>$order->billing['firstname'] . ' ' . $order->billing['lastname'],
          'email' => $order->customer['email_address'],
          'mobile_phone' => $order->customer['telephone'],
          'address[street]' => $order->billing['street_address'],
          'address[city]' => $order->billing['city'],
          'address[province]' =>$order->billing['state'],
          'address[zipcode]' => $order->billing['postcode'],

          'loginCustomer[is_guest]' => $is_guest,
          'loginCustomer[gender]' => $gender,
          'loginCustomer[full_name]' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
          'loginCustomer[num_orders]' => $order_count,
          'loginCustomer[amount_orders]' => $order_total,
          'loginCustomer[member_since]' => $sign_up,
          'loginCustomer[street]' => $order->customer['street_address'],
          'loginCustomer[city]' => $order->customer['city'],
          'loginCustomer[province]' =>$order->customer['state'],
          'loginCustomer[zipcode]' => $order->customer['postcode'],
          'loginCustomer[company]' => $order->customer['company'],
          'loginCustomer[dob]' => $dob,

          'billing[street]' => $order->billing['street_address'],
          'billing[city]' => $order->billing['city'],
          'billing[province]' =>$order->billing['state'],
          'billing[zipcode]' => $order->billing['postcode'],
          'billing[company]' => $order->billing['company'],

          'shipping[street]' => $order->delivery['street_address'],
          'shipping[city]' => $order->delivery['city'],
          'shipping[province]' =>$order->delivery['state'],
          'shipping[zipcode]' => $order->delivery['postcode'],
          'shipping[company]' => $order->delivery['company'],

          'metadata[module_version]' => $this->version,
          'metadata[platform]' => 'oscommerce '.PROJECT_VERSION
        );

        //product descirption
        $i=0;
        if (isset($order->info['shipping_method'])) {
            $submit_data["items[".$i."][description]"]=$order->info['shipping_method'];
            $submit_data["items[".$i."][quantity]"]=1;
            $submit_data["items[".$i."][amount]"]=number_format($order->info['shipping_cost'], 2, '.', '');
            $desciption[]=$order->info['shipping_method'];
            $i++;
        }

        foreach ($order->products as $product) {
            $submit_data["items[".$i."][description]"]=$product['name'];
            $submit_data["items[".$i."][quantity]"]=$product['qty'];
            $submit_data["items[".$i."][amount]"]=number_format($product['final_price'] * $product['qty'], 2, '.', '');
            $desciption[]=$product['name'] . " (".$product['qty'].")";
            $i++;
        }
        $submit_data['description'] = implode(",", $desciption);

        //$this->notify('NOTIFY_PAYMENT_AUTHNETSIM_PRESUBMIT_HOOK');

        if (MODULE_PAYMENT_PAGAMASTARDE_TESTMODE == 'Test') {
            $submit_data['x_Test_Request'] = 'TRUE';
        }
        $submit_data[tep_session_name()] = tep_session_id();

        $process_button_string = "\n";
        foreach ($submit_data as $key => $value) {
            $process_button_string .= tep_draw_hidden_field($key, $value) . "\n";
        }

        return $process_button_string;
    }

    public function before_process()
    {
        global $messageStack, $order, $db;
        $this->order_id = $_SESSION['order_id'];
        $sql = sprintf("select json from %s where order_id='%s' order by id desc limit 1", TABLE_PAGAMASTARDE, $this->order_id);
        $check_query = tep_db_query($sql);
        while ($check = tep_db_fetch_array($check_query)) {
            $this->notification = json_decode(stripcslashes($check['json']), true);
        }
        if (MODULE_PAYMENT_PAGAMASTARDE_TESTMODE == 'Test') {
            $secret_key = MODULE_PAYMENT_PAGAMASTARDE_TSK;
            $public_key = MODULE_PAYMENT_PAGAMASTARDE_TK;
        } else {
            $secret_key = MODULE_PAYMENT_PAGAMASTARDE_PSK;
            $public_key = MODULE_PAYMENT_PAGAMASTARDE_PK;
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

        if ($notififcation_check && $this->notification['event'] == 'charge.created') {
            //$this->notify('NOTIFY_PAYMENT_AUTHNETSIM_POSTSUBMIT_HOOK', $this->notification);
            $this->auth_code = 'paga+tarde';
            $this->transaction_id = $this->notification['data']['id'];
            return;
        } else {
            $messageStack->add_session('checkout_payment', MODULE_PAYMENT_PAGAMASTARDE_TEXT_DECLINED_MESSAGE, 'error');
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
        $sql = sprintf("select json from %s where order_id='%s' order by id desc limit 1", TABLE_PAGAMASTARDE, $this->order_id);
        $check_query = tep_db_query($sql);
        while ($check = tep_db_fetch_array($check_query)) {
            $this->notification = json_decode(stripcslashes($check['json']), true);
        }
        if (MODULE_PAYMENT_PAGAMASTARDE_TESTMODE == 'Test') {
            $secret_key = MODULE_PAYMENT_PAGAMASTARDE_TSK;
            $public_key = MODULE_PAYMENT_PAGAMASTARDE_TK;
        } else {
            $secret_key = MODULE_PAYMENT_PAGAMASTARDE_PSK;
            $public_key = MODULE_PAYMENT_PAGAMASTARDE_PK;
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
            $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAGAMASTARDE_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        $this->_check_install_pmt_table();
        return $this->_check;
    }

    public function install()
    {
        global $messageStack;
        if (defined('MODULE_PAYMENT_PAGAMASTARDE_STATUS')) {
            $messageStack->add_session('Paga+Tarde - Authorize.net protocol module already installed.', 'error');
            tep_redirect(tep_href_link(FILENAME_MODULES, 'set=payment&module=pagamastarde', 'NONSSL'));
            return 'failed';
        }
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Paga+Tarde Module', 'MODULE_PAYMENT_PAGAMASTARDE_STATUS', 'True', 'Do you want to accept Paga+Tarde payments?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('TEST Public Key', 'MODULE_PAYMENT_PAGAMASTARDE_TK', 'tk_XXXX', 'The test public key used for the Paga+Tarde service', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('TEST Secret Key', 'MODULE_PAYMENT_PAGAMASTARDE_TSK', 'secret', 'The test secret key used for the Paga+Tarde service', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('REAL Public Key', 'MODULE_PAYMENT_PAGAMASTARDE_PK', 'pk_XXXX', 'The real public key used for the Paga+Tarde service', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('REAL Secret Key', 'MODULE_PAYMENT_PAGAMASTARDE_PSK', 'secret', 'The real public key used for the Paga+Tarde service', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Discount', 'MODULE_PAYMENT_PAGAMASTARDE_DISCOUNT', 'False', 'Do you want to asume loan comissions?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Include Widget', 'MODULE_PAYMENT_PAGAMASTARDE_WIDGET', 'False', 'Do you want to include the Paga+Tarde widget in the checkout page?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Mode', 'MODULE_PAYMENT_PAGAMASTARDE_TESTMODE', 'Test', 'Transaction mode used for processing orders.<br><strong>Production</strong>=Live processing with real account credentials<br><strong>Test</strong>=Simulations with real account credentials', '6', '0', 'tep_cfg_select_option(array(\'Test\', \'Production\'), ', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PAGAMASTARDE_SORT_ORDER', '0', 'Sort order of displaying payment options to the customer. Lowest is displayed first.', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAGAMASTARDE_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PAGAMASTARDE_ORDER_STATUS_ID', '2', 'Set the status of orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");

        $this->_check_install_pmt_table();
    }

    public function _check_install_pmt_table()
    {
        $CheckTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGAMASTARDE."'");
        if (tep_db_num_rows($CheckTable) <= 0) {
            $sql = "CREATE TABLE " . TABLE_PAGAMASTARDE . " (
            `id` int(11) NOT NULL auto_increment,
            `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `order_id` varchar(150) NOT NULL,
            `json` TEXT,
            PRIMARY KEY (id),
            KEY (order_id))";
            tep_db_query($sql);
        }
    }

    public function remove()
    {
        $CheckTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGAMASTARDE."'");
        if (tep_db_num_rows($CheckTable) > 0) {
            tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        }
        tep_db_query("drop table " . TABLE_PAGAMASTARDE);
    }

    /**
    * Internal list of configuration keys used for configuration of the module
    *
    * @return array
    */
    public function keys()
    {
        return array('MODULE_PAYMENT_PAGAMASTARDE_STATUS',
           'MODULE_PAYMENT_PAGAMASTARDE_TK',
           'MODULE_PAYMENT_PAGAMASTARDE_TSK',
           'MODULE_PAYMENT_PAGAMASTARDE_PK',
           'MODULE_PAYMENT_PAGAMASTARDE_PSK',
           'MODULE_PAYMENT_PAGAMASTARDE_DISCOUNT',
           'MODULE_PAYMENT_PAGAMASTARDE_WIDGET',
           'MODULE_PAYMENT_PAGAMASTARDE_TESTMODE',
           'MODULE_PAYMENT_PAGAMASTARDE_SORT_ORDER',
           'MODULE_PAYMENT_PAGAMASTARDE_ZONE',
           'MODULE_PAYMENT_PAGAMASTARDE_ORDER_STATUS_ID');
    }
}
