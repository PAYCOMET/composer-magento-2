# PAYTPV Magento 2 Module

Accept payments with PAYTPV using Magento2. Suports Magento2 version 2.1 and higher.

## Description

Integrates the PAYTPV platform in Magento2

## Requirements

* Magento 2.*
* PHP >= 5.6.0
* Magento version as specified in composer.json of this project
* PAYPV account ([Account registration](https://www.paytpv.com/es/alta-empresa))

## Installation

### Install the PAYTPV Magento 2 composer package

```composer require paytpv/payment```

### Enable the extension in Magento 2

```bin/magento module:enable Paytpv_Payment --clear-static-content```

### Setup the extension and refresh cache

```bin/magento setup:upgrade```

```bin/magento cache:flush```

```bin/magento setup:di:compile```

```bin/magento setup:static-content:deploy```


## Configuration

Once installed, this module can be configured in the usual way by logging into the Magento admin area and navigating to:

Stores > Configuration > Sales > Payment Methods > PAYTPV

More details are available in the PAYTPV:

http://developers.paytpv.com/es/modulos-de-pago/magento2