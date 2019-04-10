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


define('TABLE_PAGANTIS_LOG', 'pagantis_log');
define('TABLE_PAGANTIS_CONFIG', 'pagantis_config');
define('TABLE_PAGANTIS_ORDERS', 'pagantis_orders');
define('TABLE_PAGANTIS_CONCURRENCY', 'pagantis_concurrency');

class notifyController
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
     * Validation vs PagantisClient
     *
     * @return array|Array_
     * @throws Exception
     */
    public function processInformation()
    {
        require_once('vendor/autoload.php');
        try {
            $this->checkConcurrency();
            $this->getMerchantOrder();
            $this->getPagantisOrderId();
            $this->getPagantisOrder();
            $this->checkOrderStatus();
            $this->checkMerchantOrderStatus();
            $this->validateAmount();
            //$this->processMerchantOrder(); //ESTE PASO SE HACE EN EL CHECKOUT_PROCESS
        } catch (\Exception $exception) {
            $jsonResponse = new JsonExceptionResponse();
            $jsonResponse->setMerchantOrderId($this->oscommerceOrderId);
            $jsonResponse->setPagantisOrderId($this->pagantisOrderId);
            $jsonResponse->setException($exception);
            $response = $jsonResponse->toJson();
            $this->insertLog($exception);
            $shippingUrl = trim(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL', false));

            if ($this->origin == 'notify') {
                $jsonResponse->printResponse();
            } else {
                header("Location: $shippingUrl");
                exit;
            }
        }
    }

    public function confirmInformation()
    {
        try {
            $this->confirmPagantisOrder();
            $this->updateBdInfo();
            $jsonResponse = new JsonSuccessResponse();
            $jsonResponse->setMerchantOrderId($this->oscommerceOrderId);
            $jsonResponse->setPagantisOrderId($this->pagantisOrderId);
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
        //$this->checkConcurrencyTable();
        //$this->unblockConcurrency();
        //$this->blockConcurrency();
    }

    /**
     * @throws MerchantOrderNotFoundException
     */
    private function getMerchantOrder()
    {
        global $order;
        $this->oscommerceOrder = $order;
        if (!isset($order->info)) {
            throw new MerchantOrderNotFoundException();
        }
    }

    /**
     * @throws NoIdentificationException
     */
    private function getPagantisOrderId()
    {
        $query = "select pagantis_order_id from ".TABLE_PAGANTIS_ORDERS." where os_order_reference='".$this->oscommerceOrderId."'";
        $resultsSelect = tep_db_query($query);
        while ($orderRow = tep_db_fetch_array($resultsSelect)) {
            $this->pagantisOrderId = $orderRow['pagantis_order_id'];
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
            if ($this->findOscommerceOrderId()!=='') {
                return;
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
        global $order;

        if ($order->info['order_status']!=='1') {
            throw new AlreadyProcessedException();
        }
    }

    /**
     * @throws AmountMismatchException
     */
    private function validateAmount()
    {
        $pagantisAmount = $this->pagantisOrder->getShoppingCart()->getTotalAmount();
        $ocAmount = intval($this->oscommerceOrder->info['total'] * 100);

        if ($pagantisAmount != $ocAmount) {
            throw new AmountMismatchException($pagantisAmount, $ocAmount);
        }
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
        if ($this->oscommerceOrderId == "") {
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
                $query = "delete from ".TABLE_PAGANTIS_CONCURRENCY." where id='$orderId'";
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

    /**
     * @return mixed
     */
    private function findOscommerceOrderId()
    {
        $query = "select os_order_id from ".TABLE_PAGANTIS_ORDERS." where os_order_reference='".$this->oscommerceOrderId."'";
        $resultsSelect = tep_db_query($query);
        $orderRow = tep_db_fetch_array($resultsSelect);

        return $orderRow['os_order_id'];
    }

    /** STEP 6 CMOS - Check Merchant Order Status */
    /** STEP 7 VA - Validate Amount */
    /** STEP 8 PMO - Process Merchant Order */

    /**
     * Save the order status with the related identification
     */
    private function updateBdInfo()
    {
        global $insert_id, $order;
        $query = "update ".TABLE_PAGANTIS_ORDERS." set os_order_id='$insert_id' where os_order_reference='$this->oscommerceOrderId'";
        tep_db_query($query);

        $comment = "Pagantis id=$this->pagantisOrderId/Via=".$this->origin;
        $query = "insert into ".TABLE_ORDERS_STATUS_HISTORY ."(comments, orders_id, orders_status_id, customer_notified, date_added) values
            ('$comment', ".$insert_id.", '2', -1, now() )";
        tep_db_query($query);

        $query = "update ".TABLE_ORDERS." set orders_status='2' where orders_id='$insert_id'";
        tep_db_query($query);
    }

    /** STEP 9 CPO - Confirmation Pagantis Order */
    private function rollbackMerchantOrder()
    {
        global $insert_id;
        $query = "update orders set order_status='1' where orders_id='$insert_id' ";
        tep_db_query($query);
    }

    /**
     * @param $exception
     */
    private function insertLog($exception)
    {
        if ($exception instanceof \Exception) {
            $logEntry= new LogEntry();
            $logEntryJson = $logEntry->error($exception)->toJson();

            $query = "insert into ".TABLE_PAGANTIS_LOG."(log) values ('$logEntryJson')";
            tep_db_query($query);
        }
    }

    /***
     * SETTERS Y GETTERS
     */

    /**
     * @return mixed
     */
    public function getOscommerceOrderId()
    {
        return $this->oscommerceOrderId;
    }

    /**
     * @param $oscommerceOrderId
     */
    public function setOscommerceOrderId($oscommerceOrderId)
    {
        $this->oscommerceOrderId = $oscommerceOrderId;
    }

    /**
     * @return mixed
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param mixed $origin
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }

    /**
     * @return String
     */
    public function getOrderStatus()
    {
        return $this->orderStatus;
    }

    /**
     * @param String $orderStatus
     */
    public function setOrderStatus($orderStatus)
    {
        $this->orderStatus = $orderStatus;
    }
}
