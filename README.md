# drutiny-acquia
An Acquia specific Drutiny plugin for auditing your Drupal application and Acquia Cloud configuration.

## Installation

The recommended installation method is to download the Acquia Drutiny Phar file. This is a self-contained application
that bundles up everything needed to run Drutiny.

### Requirements

In order to run Acquia Drutiny its recommended you are running 

* PHP 7.2 or later with XML and BCMath extensions

### Download

1. Download the latest phar file from the [GitHub releases](https://github.com/drutiny/plugin-acquia/releases).
2. Move the phar file into `/usr/local/bin` so that its accessible from your path:
  
  ```
  mv drutinyacquia-<latest>.phar /usr/local/bin/drutiny
  ```

3. Adjust the permissions so the phar is executable
  
  ```
  chmod +x /usr/local/bin/drutiny
  ```

### Plugin Setup

Drutiny's plugin system alows Drutiny to connect to other systems in order to retrieve data for evaluating. The Acquia Cloud plugin for Drutiny provides access to Acquia Cloud's API (among other systems) and requires an access token to do so.

Plugin | Purpose
-------|--------
Acquia Lift | Access to Lift Profile Manager
Acquia Cloud APIv2 | Used to pull information about Acquia applications and environments.
Cloudflare API | Used to check configuration and pull traffic analytics.
GitHub | Used to connect to GitHub to retrieve updates, `drutiny self-update`.

You can find out which
plugins drutiny comes with by running `drutiny plugin:list`.

When you first install `Acquia Drutiny` you won't have any plugins installed like the
above output. To install a plugin, you can run `plugin:setup`:

```
$ drutiny plugin:setup acquia_api_v2
key_id (string)
Your Key ID to connect to the Acquia Cloud API v2 with. To generate an
API access token, login to https://cloud.acquia.com, then visit
https://cloud.acquia.com/#/profile/tokens, and click **Create Token**:
 :
```

Follow the instructions for each plugin to setup API keys, secrets, and tokens
for each service.

### Updates (self-update)
`drutiny` can auto update itself by running `drutiny self-update`.

### Hello World

Run the following command to checkout if Drutiny is working
correctly. Navigate the installation directory of a drupal site so
Drutiny can access drush.

```
drutiny profile:run test @none
```

## Usage

Drutiny is a Symfony Console application and as such as a number
of commands available. Simply run `drutiny` to
see a list of commands and use the `help` command to see more options
available for a specific command.

### Finding available profiles (reports) to run.

Drutiny uses **profiles** to group audits and policies into a report.
These reports generally reflect analysis or assessment of a best
practice.

To see what profiles are available in your installation, use the following command.

```
drutiny profile:list
```

### Finding available policies to audit.

A **policy** is a single assessment or a check. It defines what you want Drutiny to 
evaluate as well as how Drutiny should acquire the data.

To see what policies are available in your installation, use the following command.

```
drutiny policy:list
```

Acquia policies are prefixed with `acquia`.

### Running an audit

An audit of a single policy can be run against a site by using `policy:audit` and passing the policy name and site target:

```
drutiny policy:audit Drupal-8:PageCacheExpiry @drupalvm.dev
```

The command above would audit the site that resolved to the `@drupalvm.dev` drush alias against the `Drupal-8:PageCacheExpiry` policy.

Some policies have parameters you can specify which can be passed in at call time. Use `policy:info` to find out more about the parameters available for a check.

```
drutiny policy:audit -p value=600 Drupal-8:PageCacheExpiry @drupalvm.dev
```

### Running a profile

Profiles provide the most value during a site audit since a collection of policies are run and consolidated into a single report. This allows you to audit against a specific standard, company policy or best practice. Drutiny comes with some base profiles which you can find using `profile:list`. You can run a profile with `profile:run` in a simlar format to `policy:audit`.

```
drutiny profile:run d8 @drupalvm.dev
```

By default, profile runs report to the console but reports can also be exported in html and json formats.

```
drutiny profile:run d8 --format=html --report-filename=drupalvm-dev.html drush:@drupalvm.dev
```