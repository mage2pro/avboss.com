A custom module for [avboss.com](http://avboss.com).

## How to install
```
composer require mage2pro/avboss.com:*
bin/magento setup:upgrade
rm -rf pub/static/* && bin/magento setup:static-content:deploy en_US
rm -rf var/di var/generation generated/code && bin/magento setup:di:compile
```
If you have some problems while executing these commands, then check the [detailed instruction](https://mage2.pro/t/263).