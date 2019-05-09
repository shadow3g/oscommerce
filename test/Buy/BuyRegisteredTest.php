<?php

namespace Test\Buy;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Class BuyRegisteredTest
 * @package Test\Buy
 *
 * @group oscommerce-buy-registered
 */
class BuyRegisteredTest extends AbstractBuy
{
    /**
     * @var String $orderUrl
     */
    public $orderUrl;

    /**
     * Test Buy Registered
     */
    public function testBuyRegistered()
    {
        $this->prepareProductAndCheckout(false);
        $this->login();
        $this->fillShippingMethod();
        $this->fillPaymentMethod();

        // get cart total price
        // $button = WebDriverBy::xpath("//td[@class='main']/strong[1]");
        // $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        // $this->waitUntil($condition);
        // $cartPrice = $this->findByXpath("//td[@class='main']/strong[1]")->getText();


        // --------------------
        $this->goToPagantis();
        $this->verifyPagantis();
        $this->commitPurchase();
        $this->checkPurchaseReturn(self::CORRECT_PURCHASE_MESSAGE);
        // $this->makeValidation();
        $this->quit();
    }
}
