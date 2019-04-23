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
    var $enabled = false;


    function ht_pagantis() {
        $this->title = MODULE_HEADER_TAGS_PAGANTIS_TITLE;
        $this->description = MODULE_HEADER_TAGS_PAGANTIS_DESCRIPTION;
        $this->sort_order = 0;

        if ( defined('MODULE_HEADER_TAGS_PAGANTIS_STATUS') ) {
            $this->enabled = (MODULE_HEADER_TAGS_PAGANTIS_STATUS == 'True');
        }
        $this->extraConfig = $this->getExtraConfig();
        $this->pk = $this->getConfig('MODULE_PAYMENT_PAGANTIS_PK');
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
            $response    = array();
            while ($resultArray = tep_db_fetch_array($result)) {
                $response[$resultArray['config']] = $resultArray['value'];
            }
        }
        return $response;
    }

    /**
     * @param string $config
     * @return array
     */
    private function getConfig($config = '')
    {
            $query       = "select configuration_value from ".TABLE_CONFIGURATION . " where configuration_key = '" . $config . "'";
            $result      = tep_db_query($query);
            $resultArray = tep_db_fetch_array($result);
            return $resultArray['configuration_value'];
    }

    function execute() {
        echo "<script src='https://cdn.pagantis.com/js/pg-v2/sdk.js'></script>". PHP_EOL;
        echo '<script>'. PHP_EOL;

        echo '        function loadSimulator()'. PHP_EOL;
        echo '        {'. PHP_EOL;
        echo '           if (typeof pgSDK != \'undefined\') {'. PHP_EOL;
        echo '               var positionSelector = \'' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR']. '\';'. PHP_EOL;
        echo '               var priceSelector = \'' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR']. '\';'. PHP_EOL;
        echo '               var quantitySelector = \'' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR']. '\';'. PHP_EOL;

        echo '               if (positionSelector === \'default\') {'. PHP_EOL;
        echo '                   positionSelector = \'.buttonSet\''. PHP_EOL;
        echo '               }'. PHP_EOL;

        echo '               if (priceSelector === \'default\') {'. PHP_EOL;
        echo '                   priceSelector = \'#bodyContent>form>div>h1\''. PHP_EOL;
        echo '               }'. PHP_EOL;

        echo '               pgSDK.product_simulator = {};'. PHP_EOL;
        echo '               pgSDK.product_simulator.id = \'product-simulator\';'. PHP_EOL;
        echo '               pgSDK.product_simulator.publicKey = \'' . $this->pk . '\';'. PHP_EOL;
        echo '               pgSDK.product_simulator.selector = positionSelector;'. PHP_EOL;
        echo '               pgSDK.product_simulator.numInstalments = \'' . $this->extraConfig['PAGANTIS_SIMULATOR_START_INSTALLMENTS'] . '\';'. PHP_EOL;
        echo '               pgSDK.product_simulator.type = ' . $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_TYPE'] . ';'. PHP_EOL;
        echo '               pgSDK.product_simulator.skin = ' . $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_SKIN'] . ';'. PHP_EOL;
        echo '               pgSDK.product_simulator.position = ' . $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_CSS_POSITION'] . ';'. PHP_EOL;
        echo '               pgSDK.product_simulator.itemAmountSelector = priceSelector;'. PHP_EOL;

        echo '               pgSDK.simulator.init(pgSDK.product_simulator);'. PHP_EOL;
        echo '               clearInterval(window.PSSimulatorId);'. PHP_EOL;
        echo '               return true;'. PHP_EOL;
        echo '           }'. PHP_EOL;
        echo '           return false;'. PHP_EOL;
        echo '       }'. PHP_EOL;
        echo '       window.PSSimulatorId = setInterval(function () {'. PHP_EOL;
        echo '          loadSimulator();'. PHP_EOL;
        echo '       }, 2000);'. PHP_EOL;
        echo '</script>'. PHP_EOL;
    }

    function isEnabled() {
        return $this->enabled;
    }

    function check() {
        return defined('MODULE_HEADER_TAGS_PAGANTIS_STATUS');
    }

    function install() {
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Pagantis Module', 'MODULE_HEADER_TAGS_PAGANTIS_STATUS', 'True', '', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");

    }

    function remove() {
        tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
        return array('MODULE_HEADER_TAGS_PAGANTIS_STATUS');
    }
}
?>
