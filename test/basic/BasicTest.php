<?php

namespace Test\Basic;

use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\PagantisOscommerceTest;

/**
 * Class BasicOscommerceTest
 * @package Test\Basic
 *
 * @group oscommerce-basic
 */
class BasicTestTest extends PagantisOscommerceTest
{
    /**
     * Const title
     */
    const TITLE = 'osCommerce';
    /**
     * Const admin_title
     */
    const ADMIN_TITLE = 'Herramienta de Administración de osCommerce Online Merchant';

    /**
     * testTitleOscommerce
     */
    public function testTitleOscommerce()
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