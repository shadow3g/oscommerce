<?php

namespace Test\Basic;

use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\PagantisOscommerceTest;

/**
 * Class BasicOc3Test
 * @package Test\Basic
 *
 * @group oscommerce-basic
 */
class BasicWc3Test extends PagantisOscommerceTest
{
    /**
     * Const title
     */
    const TITLE = 'Oscommerce';

    /**
     * testTitleOscommerce3
     */
    public function testTitleOscommerce3()
    {
        $this->webDriver->get(self::WC3URL);
        $condition = WebDriverExpectedCondition::titleContains(self::TITLE);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->quit();
    }

    /**
     * testBackOfficeTitleOscommerce
     */
    public function testBackOfficeTitleOscommerce()
    {
        $this->webDriver->get(self::WC3URL.self::BACKOFFICE_FOLDER);
        $condition = WebDriverExpectedCondition::titleContains(self::TITLE);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->quit();
    }
}