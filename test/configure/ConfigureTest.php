<?php

namespace Test\Configure;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Class ConfigureTest
 * @package Test\Configure
 *
 * @group oscommerce-configure

 */
class ConfigureTest extends AbstractConfigure
{
    /**
     * testConfigurePagantisInOscommerce
     */
    public function testConfigurePagantisInOscommerce()
    {
        $this->loginToBackOffice();
        $this->goToPagantis();
        $this->installPagantisModule();
        $this->configureModule();
        $this->quit();
    }

    /**
     * Configure paylater module
     */
    public function configureModule()
    {
        // Click on edit
        $button = WebDriverBy::id('tdb2');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findById('tdb2')->click();

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
