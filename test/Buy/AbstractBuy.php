<?php

namespace Test\Buy;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Pagantis\ModuleUtils\Exception\NoIdentificationException;
use Pagantis\ModuleUtils\Exception\AlreadyProcessedException;
use Pagantis\ModuleUtils\Exception\QuoteNotFoundException;
use Pagantis\SeleniumFormUtils\SeleniumHelper;
use Test\PagantisOscommerceTest;
use Httpful\Request;

/**
 * Class AbstractBuy
 * @package Test\Buy
 */
abstract class AbstractBuy extends PagantisOscommerceTest
{
    /**
     * Product name
     */
    const PRODUCT_NAME = 'Speed';

    /**
     * Correct purchase message
     */
    const CORRECT_PURCHASE_MESSAGE = 'Su Pedido ha sido Procesado!';

    /**
     * Canceled purchase message
     */
    const CANCELED_PURCHASE_MESSAGE = 'YOUR ORDER HAS BEEN CANCELED.';

    /**
     * Shopping cart message
     */
    const SHOPPING_CART_MESSAGE = 'SHOPPING CART';

    /**
     * Empty shopping cart message
     */
    const EMPTY_SHOPPING_CART = 'SHOPPING CART IS EMPTY';

    /**
     * Pagantis Order Title
     */
    const PAGANTIS_TITLE = 'Paga+Tarde';

    /**
     * Notification route
     */
    const NOTIFICATION_FOLDER = '/pagantis/notify';

    /**
     * @param bool $promoted
     */
    public function prepareProductAndCheckout($promoted = false)
    {
        $this->goToProductPage();
        if ($promoted) {
            $this->checkPromoted();
        }
        $this->addToCart();
    }

    /**
     * testAddToCart
     */
    public function addToCart()
    {
        $addToCartButtonSearch = WebDriverBy::id('tdb4');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($addToCartButtonSearch);
        $this->waitUntil($condition);
        $addToCartButtonElement = $this->webDriver->findElement($addToCartButtonSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($addToCartButtonElement));
        $addToCartButtonElement->click();


        $buyButtonSearch = WebDriverBy::id('tdb5');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($buyButtonSearch);
        $this->waitUntil($condition);
        $buyButtonElement = $this->webDriver->findElement($buyButtonSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($buyButtonElement));
        $buyButtonElement->click();
    }

    /**
     * Login
     */
    public function login()
    {
        $this->findByName('email_address')->clear()->sendKeys($this->configuration['customeremail']);
        $this->findByName('password')->clear()->sendKeys($this->configuration['customerpwd']);

        $buttonSearch = WebDriverBy::id('tdb1');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($buttonSearch);
        $this->waitUntil($condition);
        $buttonElement = $this->webDriver->findElement($buttonSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($buttonElement));
        $buttonElement->click();
    }

    /**
     * Go to the product page
     */
    public function goToProductPage()
    {
        $this->webDriver->get(self::OSCURL);
        $productGridSearch = WebDriverBy::className('contentContainer');
        $productLinkSearch = $productGridSearch->linkText(self::PRODUCT_NAME);

        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(
                $productLinkSearch
            )
        );
        $productLinkElement = $this->webDriver->findElement($productLinkSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($productLinkElement));
        sleep(3);
        $productLinkElement->click();
        $this->assertSame(
            self::PRODUCT_NAME . ', ' . self::TITLE,
            $this->webDriver->getTitle()
        );
    }

    /**
     * Fill the shipping method information
     */
    public function fillPaymentMethod()
    {
        $button = WebDriverBy::xpath("//tr[@class='moduleRow']/td[2]/input[@value='pagantis']");
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findByXpath("//tr[@class='moduleRow']/td[2]/input[@value='pagantis']")->click();

        $buttonSearch = WebDriverBy::id('tdb6');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($buttonSearch);
        $this->waitUntil($condition);
        $buttonElement = $this->webDriver->findElement($buttonSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($buttonElement));
        $buttonElement->click();
    }

    /**
     * Fill the shipping method information
     */
    public function fillShippingMethod()
    {
        $buttonSearch = WebDriverBy::id('tdb6');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($buttonSearch);
        $this->waitUntil($condition);
        $buttonElement = $this->webDriver->findElement($buttonSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($buttonElement));
        $buttonElement->click();
    }

    /**
     * Verify Pagantis
     *
     * @throws \Exception
     */
    public function verifyPagantis()
    {
        $condition = WebDriverExpectedCondition::titleContains(self::PAGANTIS_TITLE);
        $this->webDriver->wait(300)->until($condition, $this->webDriver->getCurrentURL());
        $this->assertTrue((bool)$condition, $this->webDriver->getCurrentURL());
    }

    /**
     * Commit Purchase
     * @throws \Exception
     */
    public function commitPurchase()
    {

        $condition = WebDriverExpectedCondition::titleContains(self::PAGANTIS_TITLE);
        $this->webDriver->wait(300)->until($condition, $this->webDriver->getCurrentURL());
        $this->assertTrue((bool)$condition, "PR32");

        // complete the purchase with redirect
        SeleniumHelper::finishForm($this->webDriver);
    }


    /**
     * Complete order and open Pagantis (redirect or iframe methods)
     *
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function goToPagantis()
    {
        $buttonSearch = WebDriverBy::id('tdb5');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($buttonSearch);
        $this->waitUntil($condition);
        $buttonElement = $this->webDriver->findElement($buttonSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($buttonElement));
        $buttonElement->click();
    }

    /**
     * Close previous pagantis session if an user is logged in
     *
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function logoutFromPagantis()
    {
        // Wait the page to render (check the simulator is rendered)
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(
                WebDriverBy::name('minusButton')
            )
        );
        // Check if user is logged in in Pagantis
        $closeSession = $this->webDriver->findElements(WebDriverBy::name('one_click_return_to_normal'));
        if (count($closeSession) !== 0) {
            //Logged out
            $continueButtonSearch = WebDriverBy::name('one_click_return_to_normal');
            $continueButtonElement = $this->webDriver->findElement($continueButtonSearch);
            $continueButtonElement->click();
        }
    }

    /**
     * Verify That UTF Encoding is working
     */
    public function verifyUTF8()
    {
        $paymentFormElement = WebDriverBy::className('FieldsPreview-desc');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($paymentFormElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->assertSame(
            $this->configuration['firstname'] . ' ' . $this->configuration['lastname'],
            $this->findByClass('FieldsPreview-desc')->getText()
        );
    }

    /**
     * Check purchase return message
     *
     * @param string $message
     *
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function checkPurchaseReturn($message = '')
    {
        // Check if all goes good
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::cssSelector('#bodyContent>h1')
            )
        );
        $successMessage = $this->findByCss('#bodyContent>h1');
        $this->assertContains(
            $message,
            $successMessage->getText()
        );
    }

    public function makeValidation()
    {
        $this->checkConcurrency();
        $this->checkPagantisOrderId();
        $this->checkAlreadyProcessed();
    }


    /**
     * Check if with a empty parameter called order-received we can get a QuoteNotFoundException
     */
    protected function checkConcurrency()
    {
        $notifyUrl = self::OSCURL.self::NOTIFICATION_FOLDER.'?order=';
        $this->assertNotEmpty($notifyUrl, $notifyUrl);
        $response = Request::post($notifyUrl)->expects('json')->send();
        $this->assertNotEmpty($response->body->result, $response);
        $this->assertNotEmpty($response->body->status_code, $response);
        $this->assertNotEmpty($response->body->timestamp, $response);
        $this->assertContains(
            QuoteNotFoundException::ERROR_MESSAGE,
            $response->body->result,
            "PR=>".$response->body->result
        );
    }

    /**
     * Check if with a parameter called order-received set to a invalid identification,
     * we can get a NoIdentificationException
     */
    protected function checkPagantisOrderId()
    {
        $orderId=0;
        $notifyUrl = self::OSCURL.self::NOTIFICATION_FOLDER.'?order='.$orderId;
        $this->assertNotEmpty($notifyUrl, $notifyUrl);
        $response = Request::post($notifyUrl)->expects('json')->send();
        $this->assertNotEmpty($response->body->result, $response);
        $this->assertNotEmpty($response->body->status_code, $response);
        $this->assertNotEmpty($response->body->timestamp, $response);
        $this->assertEquals(
            $response->body->merchant_order_id,
            $orderId,
            $response->body->merchant_order_id.'!='. $orderId
        );
        $this->assertContains(
            NoIdentificationException::ERROR_MESSAGE,
            $response->body->result,
            "PR=>".$response->body->result
        );
    }

    /**
     * Check if re-launching the notification we can get a AlreadyProcessedException
     *
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    protected function checkAlreadyProcessed()
    {
        $notifyUrl = self::OSCURL.self::NOTIFICATION_FOLDER.'?order=145000008';
        $response = Request::post($notifyUrl)->expects('json')->send();
        $this->assertNotEmpty($response->body->result, $response);
        $this->assertNotEmpty($response->body->status_code, $response);
        $this->assertNotEmpty($response->body->timestamp, $response);
        $this->assertNotEmpty($response->body->merchant_order_id, $response);
        $this->assertNotEmpty($response->body->pagantis_order_id, $response);
        $this->assertContains(
            AlreadyProcessedException::ERROR_MESSAGE,
            $response->body->result,
            "PR51=>".$response->body->result
        );
    }

}