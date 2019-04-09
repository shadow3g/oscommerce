<?php
$response = array('status'=>null);
chdir('../../../../');
require('includes/application_top.php');
define('TABLE_PAGANTIS_CONFIG', 'pagantis_config');

/**
 * Variable which contains extra configuration.
 * @var array $defaultConfigs
 */
$defaultConfigs = array('PMT_TITLE'=>'Instant Financing',
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

$response = array();
$secretKey = $_GET['secret'];

$privateQuery = "select configuration_value from configuration where configuration_key = 'MODULE_PAYMENT_PAGANTIS_SK'";
$resultsSelect = tep_db_query($privateQuery);
$orderRow = tep_db_fetch_array($resultsSelect);
$privateKey = $orderRow['configuration_value'];


if ($privateKey != $secretKey) {
    $response['status'] = 401;
    $response['result'] = 'Unauthorized';
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (count($_POST)) {
        foreach ($_POST as $config => $value) {
            if (isset($defaultConfigs[$config]) && $response['status']==null) {
                $updateQuery = "update ".TABLE_PAGANTIS_CONFIG." set value='$value' where config='$config'";
                $resultsSelect = tep_db_query($updateQuery);
            } else {
                $response['status'] = 400;
                $response['result'] = 'Bad request';
            }
        }
    } else {
        $response['status'] = 422;
        $response['result'] = 'Empty data';
    }
}

$formattedResult = array();
if ($response['status']==null) {
    $query = "select * from ".TABLE_PAGANTIS_CONFIG;
    $resultsSelect = tep_db_query($query);
    while ($orderRow = tep_db_fetch_array($resultsSelect)) {
        $formattedResult[$orderRow['config']] = $orderRow['value'];
    }

    $response['result'] = $formattedResult;
}

$result = json_encode($response['result']);
header("HTTP/1.1 ".$response['status'], true, $response['status']);
header('Content-Type: application/json', true);
header('Content-Length: '.strlen($result));
echo($result);
exit();
