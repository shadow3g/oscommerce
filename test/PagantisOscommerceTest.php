<?php

namespace Test;

use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

/**
 * Class PagantisOscommerceTest
 * @package Test
 */
abstract class PagantisOscommerceTest extends TestCase
{
    const OSCURL = 'http://oscommerce-test.docker:8096?language=es';

    const OSCURL_BACKOFFICE = 'http://oscommerce-test.docker:8096/admin?language=es';

    /**
     * Const title
     */
    const TITLE = 'osCommerce';
    /**
     * Const admin_title
     */
    const ADMIN_TITLE = 'osCommerce Online Merchant Administration Tool';

    /**
     * @var array
     */
    protected $configuration = array(
        'username'            => 'root',
        'password'            => 'root',
        'publicKey'           => 'tk_fd53cd467ba49022e4f8215e',
        'secretKey'           => '21e57baa97459f6a',
        'birthdate'           => '05/05/1982',
        'customeremail'       => 'demo@oscommerce.com',
        'customerpwd'         => 'oscommerce_demo',
        'country'             => '195',
        'city'                => 'Barcelona',
        'methodName'          => 'Pagantis',
        'checkoutTitle'       => 'Instant Financing',
        'confirmationMsg'     => 'Pedido recibido',
        'checkoutDescription' => 'Pay up to 12 comfortable installments with Pagantis',
        'enter'               => 'Haz click aquí para acceder',
        'pppText'             => 'haz click aquí'
    );


    /**
     * WooCommerce constructor.
     *
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $faker = Factory::create();
        $this->configuration['dni'] = $this->getDNI();
        $this->configuration['firstname'] = $faker->firstName;
        $this->configuration['lastname'] = $faker->lastName . ' ' . $faker->lastName;
        $this->configuration['company'] = $faker->company;
        $this->configuration['zip'] = '28'.$faker->randomNumber(3, true);
        $this->configuration['address'] = $faker->streetAddress;
        $this->configuration['phone'] = '6' . $faker->randomNumber(8);
        $this->configuration['email'] = date('ymd') . '@pagantis.com';
        parent::__construct($name, $data, $dataName);
    }
    /**
     * @return string
     */
    protected function getDNI()
    {
        $dni = '0000' . rand(pow(10, 4-1), pow(10, 4)-1);
        $value = (int) ($dni / 23);
        $value *= 23;
        $value= $dni - $value;
        $letter= "TRWAGMYFPDXBNJZSQVHLCKEO";
        $dniLetter= substr($letter, $value, 1);
        return $dni.$dniLetter;
    }

    /**
     * @var RemoteWebDriver
     */
    protected $webDriver;

    /**
     * Configure selenium
     */
    protected function setUp()
    {
        $this->webDriver = PagantisWebDriver::create(
            'http://localhost:4444/wd/hub',
            DesiredCapabilities::chrome(),
            90000,
            90000
        );
    }

    /**
     * @param $name
     *
     * @return RemoteWebElement
     */
    public function findByName($name)
    {
        return $this->webDriver->findElement(WebDriverBy::name($name));
    }

    /**
     * @param $id
     *
     * @return RemoteWebElement
     */
    public function findById($id)
    {
        return $this->webDriver->findElement(WebDriverBy::id($id));
    }

    /**
     * @param $className
     *
     * @return RemoteWebElement
     */
    public function findByClass($className)
    {
        return $this->webDriver->findElement(WebDriverBy::className($className));
    }

    /**
     * @param $css
     *
     * @return RemoteWebElement
     */
    public function findByCss($css)
    {
        return $this->webDriver->findElement(WebDriverBy::cssSelector($css));
    }

    /**
     * @param $xpath
     *
     * @return RemoteWebElement
     */
    public function findByXpath($xpath)
    {
        return $this->webDriver->findElement(WebDriverBy::xpath($xpath));
    }

    /**
     * @param $link
     *
     * @return RemoteWebElement
     */
    public function findByLinkText($link)
    {
        return $this->webDriver->findElement(WebDriverBy::partialLinkText($link));
    }

    /**
     * @param WebDriverExpectedCondition $condition
     * @return mixed
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function waitUntil(WebDriverExpectedCondition $condition)
    {
        return $this->webDriver->wait()->until($condition);
    }

    /**
     * @param WebDriverElement $element
     *
     * @return WebDriverElement
     */
    public function moveToElementAndClick(WebDriverElement $element)
    {
        $action = new WebDriverActions($this->webDriver);
        $action->moveToElement($element);
        $action->click($element);
        $action->perform();

        return $element;
    }

    /**
     * @param WebDriverElement $element
     *
     * @return WebDriverElement
     */
    public function getParent(WebDriverElement $element)
    {
        return $element->findElement(WebDriverBy::xpath(".."));
    }

    /**
     * Quit browser
     */
    protected function quit()
    {
        $this->webDriver->quit();
    }
}