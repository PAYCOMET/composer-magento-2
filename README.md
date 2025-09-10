# PAYCOMET Magento 2 Module

Accept payments with PAYCOMET using Magento2. Suports Magento2 version 2.1 and higher.

## Description

Integrates the PAYCOMET platform in Magento2

## Requirements

* Magento 2.*
* PHP >= 5.6.0
* Magento version as specified in composer.json of this project
* PAYCOMET account ([Account registration](https://www.paycomet.com/crear-una-cuenta))

## Installation

### Install the PAYCOMET Magento 2 composer package

```composer require paycomet/payment```

### Enable the extension in Magento 2

```bin/magento module:enable Paycomet_Payment --clear-static-content```

### Setup the extension and refresh cache

```bin/magento setup:upgrade```

```bin/magento cache:flush```

```bin/magento setup:di:compile```

```bin/magento setup:static-content:deploy```


## Configuration

Once installed, this module can be configured in the usual way by logging into the Magento admin area and navigating to:

Stores > Configuration > Sales > Payment Methods > PAYCOMET

More details are available in the PAYCOMET:

https://docs.paycomet.com/es/modulos-de-pago/magento2


## Release Notes

> 2.4.41: 

- Compatiblidad Magento 2.4.8 y php 8.4.

> 2.4.40: 

- Mejoras de código. Se añaden validaciones.

> 2.4.39: 

- Mejoras de código.

> 2.4.38: 

- Validación previa antes de cancelación de pedidos

> 2.4.37: 

- Compatibilidad php 8.3

> 2.4.36: 

- Nuevas funciones Graphql

> 2.4.35: 

- MB Way. Devoluciones

> 2.4.34: 

- Graphql. Obtencion de tokens

> 2.4.33: 

- Mejora de código. Calculo información pedido con cupones.

> 2.4.32: 

- Mejora de código

> 2.4.31: 

- Registro de transacción única por cada devolución.
- Mostrar imagen de SafeKey AMEX configurable en Modal de Pago.

> 2.4.30: 

- Compatibilidad php 8.2

> 2.4.29: 

- Mejoras de código jetIframe

> 2.4.28: 

- Mejoras de código

> 2.4.27: 

- Mejoras APM InstantCredit

> 2.4.26: 

- Se mantiene el carrito al redirigir al APM

> 2.4.25: 

- Se añade APM Waylet

> 2.4.24: 

- Se añade APM MB Way

> 2.4.23: 

- Control tokens inactivos. Los tarjetas caducadas ya no se muestrasn en el checkout.

> 2.4.22: 

- Mejoras de código. Los pedidos de los APMs se crean inicialmente en estado pending_payment a excepción de Multibanco que se crea en pending.

> 2.4.21: 

- Validación Magento 2.4.4 y php 8.1
- Mejoras APM InstantCredit. Configuración límites

> 2.4.20: 

- Mejoras APM InstantCredit

> 2.4.19: 

- Se añaden a lista blanca dominios para diferentes policies.
- Mejoras de código en integración jetIframe.

> 2.4.18: 

- Se añade opcion para configurar el estado de los nuevos pedidos en los APMs.
- Imagen png de Multibanco

> 2.4.17: 

- Se añaden variables de Multibanco para poder usar en templates

> 2.4.16: 

- Mejora de código

> 2.4.15: 

- APM Multibanco: Se incorporan datos

> 2.4.14: 

- Mejoras de codigo. Llamada al formulario de pago.

> 2.4.13: 

- Se añade APM KlarnaPayments y Paypal

> 2.4.12: 

- Parametros configurables por store view

> 2.4.11: 

- Soporte operativa DCC

> 2.4.10: 

- Mejoras en Preautorizaciones de Multitienda.

> 2.4.9: 

- Mejoras de código.

> 2.4.8: 

- Instant Credit: Posibilidad de configurar el simulador en Test y cambio de logo

> 2.4.7: 

- Se permite poner pedidos On-hold

> 2.4.6: 

- Fix pago con Apm sin tener pago con tarjeta Activo
- Se elimina Paypal de lo Apms

> 2.4.5: 

- Fix Alert

> 2.4.4: 

- Simulador de cuotas APM Instant Credit

> 2.4.3: 

- Cambio para que los pedidos de los APMs se queden en Pending Payment hasta que se pagan o se cancelan.
- No se envía mail de confirmación de pedido hasta que no se procesa el pago.

> 2.4.2: 

- Fix compatibilidad uso de prefijos en tablas.

> 2.4.1: 

- Compatibilidad con uso de prefijos en tablas.
- Mejoras de código

> 2.4.0: 

- **API Key [OBLIGATORIA]**: Debe dar de alta una API Key en su área de cliente de PAYCOMET e indicarla en el Plugin para poder operar.
- Integración jetIframe: Se añade la integración jetIframe.
- Métodos de Pago alternativos: Se añade la posibilidad de activar diferentes métodos de pago alternativos que deberá tener configurados en su área de cliente de PAYCOMET.
