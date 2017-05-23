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
Acquia checks are prefixed with `acquia`

```bash
./vendor/bin/drutiny check:list | grep acquia
```

## Note
This library relies on Acquia's PHP SDK to talk to Acquia Cloud API. This SDK
uses an older version of Guzzle you may see warnings about the package being abandoned.
