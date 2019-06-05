<?php

namespace Test\Configure;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\PagantisOscommerceTest;

/**
 * Class ConfigureTest
 * @package Test\Configure
 */
abstract class AbstractConfigure extends PagantisOscommerceTest
{
    /**
     * Login to the backoffice
     */
    public function loginToBackOffice()
    {
        $this->webDriver->get(self::OSCURL . self::BACKOFFICE_FOLDER);
        sleep(2);

        $usernameElementSearch = WebDriverBy::name('username');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($usernameElementSearch);
        $this->waitUntil($condition);

        $this->findByName('username')->clear()->sendKeys($this->configuration['username']);
        $this->findByName('password')->clear()->sendKeys($this->configuration['password']);


        $this->webDriver->executeScript('$("#tdb1").click();');

        $loginElements = $this->webDriver->findElements(WebDriverBy::className('messageStackError'));
        $this->assertEquals(0, count($loginElements), "Login KO");

        $elementSearch = WebDriverBy::className('headerBar');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elementSearch);
        $this->waitUntil($condition);

        $text = $this->findByClass('headerBar')->getText();

        $this->assertContains('Logged in', $text, "Login OK");
    }

    /**
     * Configure PagantisModule
     */
    public function installPagantisModule()
    {
        // click on install new module
        $button = WebDriverBy::id('tdb1');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findById('tdb1')->click();

        // click on Pagantis
        $button = WebDriverBy::xpath("//td[contains(text(), 'Paga+Tarde')]");
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findByXpath("//td[contains(text(), 'Paga+Tarde')]")->click();

        // Click on install module
        $button = WebDriverBy::id('tdb2');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findById('tdb2')->click();

    }

    /**
     * Configure paylater module
     */
    public function goToPagantis()
    {
        $this->findByLinkText('Módulos')->click();
        sleep(1);
        $this->findByLinkText('Pago')->click();
    }
}