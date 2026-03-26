# CRM GoPay Module

## Installation

We recommend using Composer for installation and update management. To add CRM GoPay extension to your [REMP CRM](https://github.com/remp2020/crm-skeleton/) application use following command:

```bash
composer require remp/crm-gopay-module
```

Enable installed extension in your `app/config/config.neon` file:

```neon
extensions:
	# ...
	gopay: Crm\GoPayModule\DI\GoPayModuleExtension
```

Seed GoPay payment gateway and its configuration:

```bash
php bin/command.php application:seed
```

## Configuration & API keys

Enter GoPay API keys to CRM

   - Visit to CRM admin Payments settings (`/admin/config-admin` - Payments)
   - Enter *GoPay Go ID* key
   - Enter *GoPay Client ID* key
   - Enter *GoPay Client secret* key
         
Keys are provided after registering at [GoPay](https://www.gopay.com).

Another two configuration options are: 

   - *GoPay Test Mode* - if enabled, all payments are done in test mode, no money charge is made. For testing, use [Testing payment cards](https://help.gopay.com/en/knowledge-base/integration-of-payment-gateway/integration-of-payment-gateway-1/testing-payments-in-the-sandbox).
   - *GoPay enabled EET* - Elektronická evidence tržeb (EET), system for reporting sales to state administration (only in Czechia). For more details, see [documentation](https://help.gopay.com/cs/tema/propojeni-do-eet/jak-funguje-napojeni-gopay-do-eet). 