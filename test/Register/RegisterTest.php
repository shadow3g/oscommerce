<?php

namespace Test\Register;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;

/**
 * Class RegisterTest
 * @package Test\Register
 *
 * @group oscommerce-register
 */
class RegisterTest extends AbstractRegister
{
    /**
     * Register into OsCommerce
     */
    public function testRegister()
    {
        $this->openOsCommerce();
        $this->goToAccountPage();
        $this->goToAccountCreate();
        $this->fillFormAndSubmit();
        $this->quit();
    }

    /**
     * Fill register form
     */
    public function fillFormAndSubmit()
    {
        $this->findByName('gender')->click();
        $this->findByName('firstname')->clear()->sendKeys($this->configuration['firstname']);
        $this->findByName('lastname')->clear()->sendKeys($this->configuration['lastname']);
        $this->findByName('dob')->clear()->sendKeys($this->configuration['birthdate']);
        $this->findByName('email_address')->clear()->sendKeys($this->configuration['customeremail']);

        $this->findByName('street_address')->clear()->sendKeys($this->configuration['address']);
        $this->findByName('postcode')->clear()->sendKeys($this->configuration['zip']);
        $this->findByName('city')->clear()->sendKeys($this->configuration['city']);
        $this->findByName('state')->clear()->sendKeys($this->configuration['city']);
        $select = new WebDriverSelect($this->findByName('country'));
        $select->selectByValue($this->configuration['country']);
        $this->findByName('telephone')->clear()->sendKeys($this->configuration['phone']);
        $this->findByName('password')->clear()->sendKeys($this->configuration['customerpwd']);
        $this->findByName('confirmation')->clear()->sendKeys($this->configuration['customerpwd']);

        $myAccountSearch = WebDriverBy::id('tdb4');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($myAccountSearch);
        $this->waitUntil($condition);
        $myAccountElement = $this->webDriver->findElement($myAccountSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($myAccountElement));
        $myAccountElement->click();

        $okMessage = WebDriverBy::cssSelector('#bodyContent>h1');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($okMessage);
        $this->webDriver->wait()->until($condition);
        $this->assertSame($this->findByCss('#bodyContent>h1')->getText(), self::SUCCESS_MESSAGE);
    }
}
