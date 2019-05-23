#!/bin/bash
npm install
# Prepare environment and build package
docker-compose down
docker-compose up -d --build oscommerce-test
if [ "$1" = "true" ]
then
    docker-compose up -d --build selenium
fi
sleep 10

grunt shell:composerProd

docker cp ./catalog/ext/. oscommerce-test:/var/www/html/ext/
docker cp ./catalog/includes/. oscommerce-test:/var/www/html/includes/
docker cp ./catalog/admin/promotion.php oscommerce-test:/var/www/html/admin/promotion.php

grunt shell:composerDev

set -e

if [ "$1" = "true" ]
then
    echo oscommerce-basic
    catalog/ext/modules/payment/pagantis/vendor/bin/phpunit --group oscommerce-basic
    echo oscommerce-configure
    catalog/ext/modules/payment/pagantis/vendor/bin/phpunit --group oscommerce-configure
    echo oscommerce-register
    catalog/ext/modules/payment/pagantis/vendor/bin/phpunit --group oscommerce-register
    echo oscommerce-buy-registered
    catalog/ext/modules/payment/pagantis/vendor/bin/phpunit --group oscommerce-buy-registered
    echo oscommerce-configure-ppp
    catalog/ext/modules/payment/pagantis/vendor/bin/phpunit --group oscommerce-configure-ppp
    echo oscommerce-buy-promoted
    catalog/ext/modules/payment/pagantis/vendor/bin/phpunit --group oscommerce-buy-promoted
else
    echo oscommerce-configure
    catalog/ext/modules/payment/pagantis/vendor/bin/phpunit --group oscommerce-configure
fi