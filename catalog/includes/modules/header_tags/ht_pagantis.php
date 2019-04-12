<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

define('TABLE_PAGANTIS_CONFIG', 'pagantis_config');

class ht_pagantis {
    var $code = 'ht_pagantis';
    var $group = 'header_tags';
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    /** @var Array $extraConfig */
    public $extraConfig;

    function ht_pagantis() {
        $this->title = MODULE_HEADER_TAGS_PAGANTIS_TITLE;
        $this->description = MODULE_HEADER_TAGS_PAGANTIS_DESCRIPTION;

        if ( defined('MODULE_HEADER_TAGS_PAGANTIS_STATUS') ) {
            $this->sort_order = MODULE_HEADER_TAGS_PAGANTIS_SORT_ORDER;
            $this->enabled = (MODULE_HEADER_TAGS_PAGANTIS_STATUS == 'True');
        }
    }

    /**
     * @return array
     */
    private function getExtraConfig()
    {
        $checkTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS_CONFIG."'");
        $response = array();
        if (tep_db_num_rows($checkTable) > 0) {
            $query       = "select * from ".TABLE_PAGANTIS_CONFIG;
            $result      = tep_db_query($query);
            $resultArray = tep_db_fetch_array($result);
            var_dump($resultArray);
            $response    = array();
            foreach ((array)$resultArray as $key => $value) {
                var_dump($value);
                $response[$key] = $value;
            }
        }
        return $response;
    }

    function execute() {
        $this->extraConfig = $this->getExtraConfig();
        echo "<script src='https://cdn.pagantis.com/js/pg-v2/sdk.js'></script>";
        echo '<script>';
        echo '        function loadSimulator()';
        echo '        {';
        echo '           if (typeof pgSDK != \'undefined\') {';
        echo '               var price = null;';
        echo '               var quantity = null;';
        echo '               var positionSelector = ' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR'];
        echo '               var priceSelector = ' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR'];
        echo '               var quantitySelector = ' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR'];

        echo '               if (positionSelector === \'default\') {';
        echo '                   positionSelector = \'.pagantisSimulator\'';
        echo '               }';

        echo '               if (priceSelector === \'default\') {';
        echo '                   priceSelector = findPriceSelector();';
        echo '                }';

        echo '               if (quantitySelector === \'default\') {';
        echo '               quantitySelector = findQuantitySelector();';
        echo '                   if (quantitySelector === \'default\') {';
        echo '                       quantity = \'1\'';
        echo '                    }';
        echo '                }';

        echo '               pgSDK.product_simulator = {};';
        echo '               pgSDK.product_simulator.id = \'product-simulator\';';
        echo '               pgSDK.product_simulator.publicKey = MODULE_PAYMENT_PAGANTIS_PK;';
        echo '               pgSDK.product_simulator.selector = positionSelector;';
        echo '               pgSDK.product_simulator.numInstalments = ' . $this->extraConfig['PAGANTIS_SIMULATOR_START_INSTALLMENTS'] . ';';
        echo '               pgSDK.product_simulator.type = ' . $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_TYPE'] . ';';
        echo '               pgSDK.product_simulator.skin = ' . $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_SKIN'] . ';';
        echo '               pgSDK.product_simulator.position = ' . $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_CSS_POSITION'] . ';';

        echo '               if (priceSelector !== \'default\') {';
        echo '                   pgSDK.product_simulator.itemAmountSelector = priceSelector;';
        echo '                }';
        echo '               if (quantitySelector !== \'default\' && quantitySelector !== \'none\') {';
        echo '                   pgSDK.product_simulator.itemQuantitySelector = quantitySelector;';
        echo '               }';
        echo '               if (price != null) {';
        echo '                   pgSDK.product_simulator.itemAmount = price;';
        echo '               }';
        echo '               if (quantity != null) {';
        echo '                   pgSDK.product_simulator.itemQuantity = quantity;';
        echo '               }';

        echo '               pgSDK.simulator.init(pgSDK.product_simulator);';
        echo '               clearInterval(window.PSSimulatorId);';
        echo '               return true;';
        echo '           }';
        echo '           return false;';
        echo '       }';
        echo '       if (!loadSimulator()) {';
        echo '           window.PSSimulatorId = setInterval(function () {';
        echo '               loadSimulator();';
        echo '           }, 2000);';
        echo '       }';
        echo '</script>';
        echo '<div class="pagantisSimulator"></div>';
    }

    function isEnabled() {
        return $this->enabled;
    }

    function check() {
        return defined('MODULE_HEADER_TAGS_PAGANTIS_STATUS');
    }

    function install() {
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Pagantis Module', 'MODULE_HEADER_TAGS_PAGANTIS_STATUS', 'True', 'Do you want to allow product titles to be added to the page title?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_HEADER_TAGS_PAGANTIS_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
        tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
        return array('MODULE_HEADER_TAGS_PAGANTIS_STATUS', 'MODULE_HEADER_TAGS_PAGANTIS_SORT_ORDER');
    }
}
?>
