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
class BasicOcTest extends PagantisOscommerceTest
{
    /**
     * Const title
     */
    const TITLE = 'osCommerce';
    /**
     * Const admin_title
     */
    const ADMIN_TITLE = 'osCommerce Online Merchant Administration Tool';

    /**
     * testTitleOscommerce3
     */
    public function testTitleOscommerce3()
    {
        $this->webDriver->get(self::OSCURL);
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
        $this->webDriver->get(self::OSCURL.self::BACKOFFICE_FOLDER);
        $condition = WebDriverExpectedCondition::titleContains(self::ADMIN_TITLE);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->quit();
    }
}