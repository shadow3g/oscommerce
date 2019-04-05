<?php

use Pagantis\OrdersApiClient\Client;
use Pagantis\ModuleUtils\Exception\AlreadyProcessedException;
use Pagantis\ModuleUtils\Exception\AmountMismatchException;
use Pagantis\ModuleUtils\Exception\MerchantOrderNotFoundException;
use Pagantis\ModuleUtils\Exception\NoIdentificationException;
use Pagantis\ModuleUtils\Exception\OrderNotFoundException;
use Pagantis\ModuleUtils\Exception\QuoteNotFoundException;
use Pagantis\ModuleUtils\Exception\UnknownException;
use Pagantis\ModuleUtils\Exception\WrongStatusException;
use Pagantis\ModuleUtils\Model\Response\JsonSuccessResponse;
use Pagantis\ModuleUtils\Model\Response\JsonExceptionResponse;
use Pagantis\ModuleUtils\Model\Log\LogEntry;

define('__ROOT__', dirname(dirname(__FILE__)));

define('TABLE_PAGANTIS', 'pagantis');
define('TABLE_PAGANTIS_LOG', 'pagantis_log');
define('TABLE_PAGANTIS_CONFIG', 'pagantis_config');
define('TABLE_PAGANTIS_ORDERS', 'pagantis_orders');
define('TABLE_PAGANTIS_CONCURRENCY', 'pagantis_concurrency');
chdir('../../../../');
require('includes/application_top.php');

class pagantisNotify
{
    /** @var mixed $pagantisOrder */
    protected $pagantisOrder;

    /** @var $string $origin */
    public $origin;

    /** @var $string */
    public $order;

    /** @var mixed $oscommerceOrderId */
    protected $oscommerceOrderId = '';

    /** @var mixed $cfg */
    protected $cfg;

    /** @var Client $orderClient */
    protected $orderClient;

    /** @var Order $oscommerceOrder */
    protected $oscommerceOrder;

    /** @var mixed $pagantisOrderId */
    protected $pagantisOrderId = '';


    /**
     * pagantisNotify constructor.
     */
    public function __construct()
    {
        $this->oscommerceOrderId = '7e93cd07dbeba82f6d77965f9bec2c7a';
        $this->getPagantisOrderId();
        $result = ($this->pagantisOrderId);
        $result = unserialize($result);
        foreach ($result as $var => $content) {
            $GLOBALS[$var] = unserialize($content);
            tep_session_register($var);
        }

        header("Location: http://oscommerce-dev.docker:8095/checkout_process.php");

    }

    public static function __callStatic($name, $arguments)
    {
        // TODO: Implement __callStatic() method.
    }

    /**
     * Validation vs PagantisClient
     *
     * @return array|Array_
     * @throws Exception
     */
    public function processInformation()
    {
        require_once(__ROOT__.'/pagantis/vendor/autoload.php');
        try {
            $this->checkConcurrency();
            $this->getMerchantOrder();
            $this->getPagantisOrderId();
            $this->getPagantisOrder();
            $this->checkOrderStatus();
            $this->checkMerchantOrderStatus();
            $this->validateAmount();
            $this->processMerchantOrder();
        } catch (\Exception $exception) {
            $jsonResponse = new JsonExceptionResponse();
            $jsonResponse->setMerchantOrderId($this->oscommerceOrderId);
            $jsonResponse->setPagantisOrderId($this->pagantisOrderId);
            $jsonResponse->setException($exception);
            $response = $jsonResponse->toJson();
            $this->insertLog($exception);
        }
        try {
            if (!isset($response)) {
                $this->confirmPagantisOrder();
                $jsonResponse = new JsonSuccessResponse();
                $jsonResponse->setMerchantOrderId($this->oscommerceOrderId);
                $jsonResponse->setPagantisOrderId($this->pagantisOrderId);
            }
        } catch (\Exception $exception) {
            $this->rollbackMerchantOrder();
            $jsonResponse = new JsonExceptionResponse();
            $jsonResponse->setMerchantOrderId($this->oscommerceOrderId);
            $jsonResponse->setPagantisOrderId($this->pagantisOrderId);
            $jsonResponse->setException($exception);
            $jsonResponse->toJson();
            $this->insertLog($exception);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $jsonResponse->printResponse();
        } else {
            return $jsonResponse;
        }
    }

    /**
     * COMMON FUNCTIONS
     */

    /**
     * @throws Exception
     */
    private function checkConcurrency()
    {
        $this->getQuoteId();
        $this->checkConcurrencyTable();
        $this->unblockConcurrency();
        $this->blockConcurrency();
    }

    /**
     * @throws MerchantOrderNotFoundException
     */
    private function getMerchantOrder()
    {
        var_dump($this->oscommerceOrder);
        die;
        if (!isset($this->oscommerceOrder)) {
            throw new MerchantOrderNotFoundException();
        }
    }

    /**
     * @throws NoIdentificationException
     */
    private function getPagantisOrderId()
    {
        $query = "select pmt_order_id from ".TABLE_PAGANTIS_ORDERS." where os_order_id='".$this->oscommerceOrderId."'";
        $resultsSelect = tep_db_query($query);
        while ($orderRow = tep_db_fetch_array($resultsSelect)) {
            $this->pagantisOrderId = $orderRow['pmt_order_id'];
        }

        if ($this->pagantisOrderId == '') {
            throw new NoIdentificationException();
        }
    }

    /**
     * @throws OrderNotFoundException
     */
    private function getPagantisOrder()
    {
        try {
            $publicKey     = trim(MODULE_PAYMENT_PAGANTIS_PK);
            $secretKey     = trim(MODULE_PAYMENT_PAGANTIS_SK);
            $this->orderClient   = new \Pagantis\OrdersApiClient\Client($publicKey, $secretKey);
            $this->pagantisOrder = $this->orderClient->getOrder($this->pagantisOrderId);
        } catch (\Exception $e) {
            throw new OrderNotFoundException();
        }
    }

    /**
     * @throws AlreadyProcessedException
     * @throws WrongStatusException
     */
    private function checkOrderStatus()
    {
        try {
            $this->checkPagantisStatus(array('AUTHORIZED'));
        } catch (\Exception $e) {
            if ($this->oscommerceOrderId!='') {
                throw new AlreadyProcessedException();
            } else {
                if ($this->pagantisOrder instanceof \Pagantis\OrdersApiClient\Model\Order) {
                    $status = $this->pagantisOrder->getStatus();
                } else {
                    $status = '-';
                }
                throw new WrongStatusException($status);
            }
        }
    }

    /**
     * @throws AlreadyProcessedException
     */
    private function checkMerchantOrderStatus()
    {
        if (!$this->oscommerceOrder->info['order_status']!=='1') {
            throw new AlreadyProcessedException();
        }
    }

    /**
     * @throws AmountMismatchException
     */
    private function validateAmount()
    {
        $pagantisAmount = $this->pagantisOrder->getShoppingCart()->getTotalAmount();
        $wcAmount = intval(strval(100 * $this->oscommerceOrder->get_total()));
        if ($pagantisAmount != $wcAmount) {
            throw new AmountMismatchException($pagantisAmount, $wcAmount);
        }
    }

    /**
     * @throws Exception
     */
    private function processMerchantOrder()
    {
        $this->saveOrder();
        $this->updateBdInfo();
    }

    /**
     * @return false|string
     * @throws UnknownException
     */
    private function confirmPagantisOrder()
    {
        try {
            $this->pagantisOrder = $this->orderClient->confirmOrder($this->pagantisOrderId);
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }

        $jsonResponse = new JsonSuccessResponse();
        return $jsonResponse->toJson();
    }
    /**
     * UTILS FUNCTIONS
     */

    /** STEP 1 CC - Check concurrency */

    /**
     * @throws QuoteNotFoundException
     */
    private function getQuoteId()
    {
        $this->oscommerceOrderId = $_GET['order_id'];
        if ($this->oscommerceOrderId == '') {
            throw new QuoteNotFoundException();
        }
    }

    /**
     * Check if concurrency table exists
     */
    private function checkConcurrencyTable()
    {
        $checkTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS_CONCURRENCY."'");
        if (tep_db_num_rows($checkTable) == 0) {
            $sql = "CREATE TABLE IF NOT EXISTS ".TABLE_PAGANTIS_CONCURRENCY." (
                            id int NOT NULL,
                            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE KEY id(id))";
            tep_db_query($sql);
        }
        return;
    }

    /**
     * Unlock the concurrency
     *
     * @param null $orderId
     * @throws Exception
     */
    private function unblockConcurrency($orderId = null)
    {
        try {
            if ($orderId == null) {
                $query = "delete from ".TABLE_PAGANTIS_CONCURRENCY." where  timestamp<".(time() - 5);
                tep_db_query($query);
            } elseif ($this->$orderId!='') {
                $query = "delete from ".TABLE_PAGANTIS_CONCURRENCY." where id=".$this->$orderId;
                tep_db_query($query);
            }
        } catch (Exception $exception) {
            throw new ConcurrencyException();
        }
    }

    /**
     * @throws \Exception
     */
    private function blockConcurrency()
    {
        try {
            $query = "INSERT INTO ".TABLE_PAGANTIS_CONCURRENCY." (id) VALUES ('$this->oscommerceOrderId')";
            tep_db_query($query);
        } catch (Exception $exception) {
            throw new ConcurrencyException();
        }
    }

    /** STEP 2 GMO - Get Merchant Order */
    /** STEP 3 GPOI - Get Pagantis OrderId */
    /** STEP 4 GPO - Get Pagantis Order */
    /** STEP 5 COS - Check Order Status */
    /**
     * @param $statusArray
     *
     * @throws \Exception
     */
    private function checkPagantisStatus($statusArray)
    {
        $pagantisStatus = array();
        foreach ($statusArray as $status) {
            $pagantisStatus[] = constant("\Pagantis\OrdersApiClient\Model\Order::STATUS_$status");
        }

        if ($this->pagantisOrder instanceof \Pagantis\OrdersApiClient\Model\Order) {
            $payed = in_array($this->pagantisOrder->getStatus(), $pagantisStatus);
            if (!$payed) {
                if ($this->pagantisOrder instanceof \Pagantis\OrdersApiClient\Model\Order) {
                    $status = $this->pagantisOrder->getStatus();
                } else {
                    $status = '-';
                }
                throw new WrongStatusException($status);
            }
        } else {
            throw new OrderNotFoundException();
        }
    }
    /** STEP 6 CMOS - Check Merchant Order Status */
    /** STEP 7 VA - Validate Amount */
    /** STEP 8 PMO - Process Merchant Order */
    /**
     * @throws \Exception
     */
    private function saveOrder()
    {
        global $oscommerce;
        $paymentResult = $this->oscommerceOrder->payment_complete();
        if ($paymentResult) {
            $this->oscommerceOrder->add_order_note("Notification received via $this->origin");
            $this->oscommerceOrder->reduce_order_stock();
            $this->oscommerceOrder->save();

            $oscommerce->cart->empty_cart();
            sleep(3);
        } else {
            throw new UnknownException('Order can not be saved');
        }
    }

    /**
     * Save the merchant order_id with the related identification
     */
    private function updateBdInfo()
    {
        global $wpdb;

        $this->checkDbTable();
        $tableName = $wpdb->prefix.self::ORDERS_TABLE;

        $wpdb->update(
            $tableName,
            array('wc_order_id'=>$this->oscommerceOrderId),
            array('id' => $this->oscommerceOrderId),
            array('%s'),
            array('%d')
        );
    }

    /** STEP 9 CPO - Confirmation Pagantis Order */
    private function rollbackMerchantOrder()
    {
        $this->oscommerceOrder->update_status('pending', __('Pending payment', 'oscommerce'));
    }

    /**
     * @param $exceptionMessage
     *
     * @throws \Zend_Db_Exception
     */
    private function insertLog($exception)
    {
        global $wpdb;

        if ($exception instanceof \Exception) {
            $this->checkDbLogTable();
            $logEntry= new LogEntry();
            $logEntryJson = $logEntry->error($exception)->toJson();

            $tableName = $wpdb->prefix.self::LOGS_TABLE;
            $wpdb->insert($tableName, array('log' => $logEntryJson));
        }
    }
}

$pgNotify = new pagantisNotify();
$pgNotify->processInformation();