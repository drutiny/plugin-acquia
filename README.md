# drutiny-acquia
Acquia drives and checks for Drutiny.

## Installation

```bash
echo '{}' > composer.json
composer config minimum-stability dev
composer require --dev drutiny/acquia:dev-master
```

## Generating an API access token

To generate an API access token, login to [https://cloud.acquia.com](), then visit [https://cloud.acquia.com/#/profile/tokens](), and click ***Create Token***.

* Provide a label for the access token, so it can be easily identified. Click ***Create Token***.
* The token has been generated, copy the api key and api secret to a secure place. Make sure you record it now: you will not be able to retrieve this access token's secret again.

Once you have your API credentials, run the `plugin:setup` command to configure
the Acquia plugin with Drutiny.

```
./vendor/bin/drutiny plugin:setup cloud_api_v2
```

## Usage
Acquia policies are prefixed with `acquia`

```bash
./vendor/bin/drutiny policy:list | grep Acquia
```

Use `profile:run` loading in domains from Acquia Cloud API:

```
./vendor/bin/drutiny profile:run --domain-source=ac cloud @site.env
```

See `drutiny help profile:run` for more options to load and filter domains from
Acquia Cloud and Acquia Cloud Site Factory.
