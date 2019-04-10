<?php

namespace Test\Register;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\PagantisOscommerceTest;

/**
 * Class AbstractRegister
 * @package Test\Register
 */
abstract class AbstractRegister extends PagantisOscommerceTest
{
    /**
     * Success message
     */
    const SUCCESS_MESSAGE = 'Your Account Has Been Created!';

    /**
     * OpenOsCommerce page
     */
    public function openOsCommerce()
    {
        $this->webDriver->get(self::OSCURL);

        $this->webDriver->wait(10, 500)->until(
            WebDriverExpectedCondition::titleContains(
                self::TITLE
            )
        );

        $this->assertEquals(self::TITLE, $this->webDriver->getTitle());
    }

    /**
     * Go to my account page
     */
    public function goToAccountPage()
    {
        $myAccountSearch = WebDriverBy::id('tdb3');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($myAccountSearch);
        $this->waitUntil($condition);
        $myAccountElement = $this->webDriver->findElement($myAccountSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($myAccountElement));
        $myAccountElement->click();
    }

    /**
     * Go To account create
     */
    public function goToAccountCreate()
    {
        $myAccountSearch = WebDriverBy::id('tdb2');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($myAccountSearch);
        $this->waitUntil($condition);
        $myAccountElement = $this->webDriver->findElement($myAccountSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($myAccountElement));
        $myAccountElement->click();
    }
}
