# Configuration

## :house: Access

To access to Paga+Tarde admin panel, we need to open the oscommerce admin panel and follow the next steps:

1 – Modules => Payment
![Step 1](./oscommerce_configuration_1.png?raw=true "Step 1")

2 – Pagantis => Edit
![Step 2](./oscommerce_configuration_2.png?raw=true "Step 2")

3 – Pagantis
![Step 3](./oscommerce_configuration_3.png?raw=true "Step 3")

## :clipboard: Options
In Pagantis admin panel, we can set the following options:

| Field | Description<br/><br/>
| :------------- |:-------------| 
| Module is enabled    |  - Yes => Activate the payment method <br/> - No => Disabled the payment method
| Public Key(*)        |  String you can get from your [Paga+Tarde profile](https://bo.pagamastarde.com/shop).
| Secret Key(*)        |  String you can get from your [Paga+Tarde profile](https://bo.pagamastarde.com/shop).
| Simulator is enabled |  - Yes => Activate the installments simulator <br/> - No => Disabled the simulator


## :clipboard: Advanced configuration:
The module has many configuration options you can set, but we recommend use it as is.

If you want to manage it, you have a way to update the values via HTTP, you only need to make a post to:

<strong>{your-domain-url}ext/modules/payment/pagantis/configController.php?secret={your-secret-key}

sending in the form data the key of the config you want to change and the new value.


Here you have a complete list of configurations you can change and it's explanation. 


| Field | Description<br/><br/>
| :------------- |:-------------| 
| PAGANTIS_TITLE                           | Payment title to show in checkout page. By default:"Instant financing".
| PAGANTIS_SIMULATOR_DISPLAY_TYPE          | Installments simulator skin inside product page, in positive case. Recommended value: 'pmtSDK.simulator.types.SIMPLE'.
| PAGANTIS_SIMULATOR_DISPLAY_SKIN          | Skin of the product page simulator. Recommended value: 'pmtSDK.simulator.skins.BLUE'.
| PAGANTIS_SIMULATOR_DISPLAY_POSITION      | Choose the place where you want to watch the simulator.
| PAGANTIS_SIMULATOR_START_INSTALLMENTS    | Number of installments by default to use in simulator.
| PAGANTIS_SIMULATOR_DISPLAY_CSS_POSITION  | he position where the simulator widget will be injected. Recommended value: 'pmtSDK.simulator.positions.INNER'.
| PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR    | CSS selector with DOM element having totalAmount value.
| PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR | CSS Selector to inject the widget. (Example: '#simulator', '.PmtSimulator')
| PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR | CSS selector with DOM element having the quantity selector value.
| PAGANTIS_FORM_DISPLAY_TYPE               | Allow you to select the way to show the payment form in your site
| PAGANTIS_DISPLAY_MIN_AMOUNT              | Minimum amount to use the module and show the payment method in the checkout page.
| PAGANTIS_URL_OK                          | Location where user will be redirected after a successful payment. This string will be concatenated to the base url to build the full url
| PAGANTIS_URL_KO                          | Location where user will be redirected after a wrong payment. This string will be concatenated to the base url to build the full url
| PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR_CHECKOUT | CSS selector with DOM element having totalAmount value on checkout page.