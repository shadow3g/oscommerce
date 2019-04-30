<?php

namespace Test\Buy;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\PagantisOscommerceTest;

/**
 * Class AbstractBuy
 * @package Test\Buy
 */
abstract class AbstractBuy extends PagantisOscommerceTest
{
    /**
     * Product name
     */
    const PRODUCT_NAME = 'Matrox G200 MMS';

    /**
     * Correct purchase message
     */
    const CORRECT_PURCHASE_MESSAGE = 'Your Order Has Been Processed!';

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
     * Buy unregistered
     */
    public function prepareProductAndCheckout()
    {
        $this->goToProductPage();
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
     * Go to the product page
     */
    public function goToProductPage()
    {
        $this->webDriver->get(self::OSCURL);
        $productGridSearch = WebDriverBy::className('contentText');
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
}