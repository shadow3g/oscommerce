<?php

namespace Test\Configure;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\PagantisOscommerceTest;

/**
 * Class ConfigureTest
 * @package Test\Configure
 *
 * @group oscommerce-configure

 */
class ConfigureTest extends PagantisOscommerceTest
{
    /**
     * testConfigurePagantisInOscommerce15
     */
    public function testConfigureAndConfigurePagantisInOscommerce()
    {
        $this->loginToBackOffice();
        $this->installPagantisModule();
        $this->configureModule();
        $this->quit();
    }

    /**
     * Login to the backoffice
     */
    public function loginToBackOffice()
    {
        $this->webDriver->get(self::OSCURL.self::BACKOFFICE_FOLDER);
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
        $this->findByLinkText('Modules')->click();
        sleep(1);
        $this->findByLinkText('Payment')->click();

        // click on install new module
        $button = WebDriverBy::id('tdb1');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findById('tdb1')->click();

        // click on Pagantis
        $button = WebDriverBy::xpath("//td[contains(text(), 'Pagantis')]");
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findByXpath("//td[contains(text(), 'Pagantis')]")->click();

        // Click on install module
        $button = WebDriverBy::id('tdb2');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findById('tdb2')->click();

    }

    /**
     * Configure paylater module
     */
    public function configureModule()
    {
        // click on Edit
        $button = WebDriverBy::id('tdb2');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findById('tdb2')->click();

        //enter PK and secret
        $pkElementSearch = WebDriverBy::name('configuration[MODULE_PAYMENT_PAGANTIS_PK]');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($pkElementSearch);
        $this->waitUntil($condition);

        $this->findByName('configuration[MODULE_PAYMENT_PAGANTIS_PK]')
            ->clear()
            ->sendKeys($this->configuration['publicKey'])
        ;

        $this->findByName('configuration[MODULE_PAYMENT_PAGANTIS_SK]')
            ->clear()
            ->sendKeys($this->configuration['secretKey'])
        ;

        // click on Save
        $button = WebDriverBy::id('tdb2');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findById('tdb2')->click();
    }
}
