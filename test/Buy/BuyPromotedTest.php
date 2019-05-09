<?php

namespace Test\Buy;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Class BuyPromotedTest
 * @package Test\Buy
 *
 * @group oscommerce-buy-promoted
 */
class BuyPromotedTest extends AbstractBuy
{
    /**
     * @var String $orderUrl
     */
    public $orderUrl;

    /**
     * Test Buy Promoted
     */
    public function testBuyPromoted()
    {
        $this->prepareProductAndCheckout(true);
        $this->quit();
        die;
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
        $this->quit();
    }

    /**
     *
     */
    protected function checkPromoted()
    {
        $elememt = WebDriverBy::id("promotedText");
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elememt);
        $this->waitUntil($condition);

    }
}
