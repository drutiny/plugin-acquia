# drutiny-acquia
Acquia drives and checks for Drutiny.

## Installation
Ensure your `composer.json` file looks like this:

```json
{
    "minimum-stability": "dev",
    "require-dev": {
        "drutiny/acquia": "dev-master"
    }
}
```

Then run composer install to install drutiny/acquia.

```bash
composer install
```

Alternately, you can run installation like this:

```bash
echo '{}' > composer.json
composer config minimum-stability dev
composer require --dev drutiny/acquia:dev-master
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

## Note
This library relies on Acquia's PHP SDK to talk to Acquia Cloud API. This SDK
uses an older version of Guzzle you may see warnings about the package being abandoned.
