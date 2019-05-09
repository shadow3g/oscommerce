<?php

namespace Test\Configure;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Class ConfigureTest
 * @package Test\Configure
 *
 * @group oscommerce-configure-ppp

 */
class ConfigurePPPTest extends AbstractConfigure
{
    /**
     * testConfigurePagantisInOscommerce15
     */
    public function testConfigureAndConfigurePagantisInOscommerce()
    {
        $this->loginToBackOffice();
        $this->goToPagantis();
        $this->configurePPP();
        $this->quit();
    }

    /**
     * Configure paylater module
     */
    public function configurePPP()
    {
        // click on Pagantis
        $button = WebDriverBy::xpath("//td[contains(text(), 'Pagantis')]");
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findByXpath("//td[contains(text(), 'Pagantis')]")->click();

        // click on manage PPP
        $this->findByLinkText($this->configuration['pppText'])->click();

        // click on Matrox G200 MMS
        $this->findByName('checkboxProducts[1]')->click();
        // click on Save
        $button = WebDriverBy::id('tdb2');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($button);
        $this->waitUntil($condition);
        $this->findById('tdb2')->click();
    }
}
