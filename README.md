# About BAV

BAV (bank account validator) is a validation library for German bank accounts.


# Installation

Requires PHP 8.3 or later.

Use [Composer](https://getcomposer.org/) to install the current development
version:

```sh
composer require rotdrop/bav:dev-master@dev
```

The `@dev` stability flag applies only to BAV and does not lower your project's
global minimum stability. Use a stable version constraint when a release with
the `rotdrop\bav` namespace is available.

Include Composer's autoloader before using BAV:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

Classes use the `rotdrop\bav` namespace prefix and are loaded via PSR-4 from
`src/`. Sub-namespaces mirror the original directory hierarchy, with an
uppercase first letter for each directory and namespace segment. For example,
`classes/dataBackend/file/FileDataBackend.php` is now
`src/DataBackend/File/FileDataBackend.php`, declaring
`rotdrop\bav\DataBackend\File\FileDataBackend`.

The facade is `rotdrop\bav\BAV`. This release changes the namespace from
`malkusch\bav` to `rotdrop\bav`; update imports and class-name strings when
upgrading. No compatibility aliases for the old namespace are provided.
Common class names include:

| Class | New fully qualified name |
|---|---|
| `Bank` | `rotdrop\bav\Bank\Bank` |
| `DefaultConfiguration` | `rotdrop\bav\Configuration\DefaultConfiguration` |
| `PDODataBackendContainer` | `rotdrop\bav\DataBackend\Pdo\PDODataBackendContainer` |
| `AutomaticUpdatePlan` | `rotdrop\bav\DataBackend\Update\AutomaticUpdatePlan` |
| `Validator00` | `rotdrop\bav\Validator\Validators\Validator00` |

The generated `autoloader/autoloader.php` is no longer used; replace
any direct includes of it with Composer's autoloader. Run `composer install`
after checking out the repository, or `composer dump-autoload` after changing
autoload mappings in an existing installation.

# Configuration

You can use BAV out of the box. BAV comes with a ready to play default
configuration ([`DefaultConfiguration`](http://bav-php.github.io/bav/api/class-rotdrop.bav.DefaultConfiguration.html)):

* `UTF-8` encoding (if supported)

* [`FileDataBackendContainer`](http://bav-php.github.io/bav/api/class-rotdrop.bav.FileDataBackendContainer.html).
I.e. it uses binary search on the file from the Bundesbank.
Note that this data backend uses the directory `bav/data` for install and update
operations. You have to make sure that this directory is writable.

* automatic installation. You don't have to call any installation
script. The container will download the Bundesbank file upon the first execution.

* update plan which triggers an E_USER_NOTICE if the Bundesbank file
is outdated.

You can define your own configuration by calling
[`ConfigurationRegistry::setConfiguration()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.ConfigurationRegistry.html#_setConfiguration)
or preferably creating the file `bav/configuration.php` which returns a
[`Configuration`](http://bav-php.github.io/bav/api/class-rotdrop.bav.Configuration.html) object:

```php
namespace rotdrop\bav;

use rotdrop\bav\Configuration\DefaultConfiguration;
use rotdrop\bav\DataBackend\Pdo\PDODataBackendContainer;
use rotdrop\bav\DataBackend\Update\AutomaticUpdatePlan;

$configuration = new DefaultConfiguration();

$pdo = new \PDO("mysql:host=localhost;dbname=test;charset=UTF8");
$configuration->setDataBackendContainer(new PDODataBackendContainer($pdo));

$configuration->setUpdatePlan(new AutomaticUpdatePlan());

return $configuration;
```


# Update

The Bundesbank releases new files for March, June, September and December.
BAV needs those new files. You have several possiblities to update bav:

## Script

Call `bin/bav-update.php`.

## Programmatically

```php
use rotdrop\bav\BAV;

$bav = new BAV();
$bav->update();
```

## Automatic

Enable automatic updates with
[`AutomaticUpdatePlan`](http://bav-php.github.io/bav/api/class-rotdrop.bav.AutomaticUpdatePlan.html)
in your `bav/configuration.php`:

```php
namespace rotdrop\bav;

use rotdrop\bav\Configuration\DefaultConfiguration;
use rotdrop\bav\DataBackend\Update\AutomaticUpdatePlan;

$configuration = new DefaultConfiguration();
$configuration->setUpdatePlan(new AutomaticUpdatePlan());

return $configuration;
```
This automatic update plan will perform long running update operations as a shutdown
hook. I.e. it won't bother users during normal operations.


# Usage

You can use BAV with the api facade
[`BAV`](http://bav-php.github.io/bav/api/class-rotdrop.bav.BAV.html):

* [`BAV::isValidBank($bankID)`](http://bav-php.github.io/bav/api/class-rotdrop.bav.BAV.html#_isValidBank):
Returns true for existing bank ids.

* [`BAV::isValidBankAccount($bankID, $account)`](http://bav-php.github.io/bav/api/class-rotdrop.bav.BAV.html#_isValidBankAccount):
Returns true for existing accounts of an existing bank.

* [`BAV::isValidAccount($account)`](http://bav-php.github.io/bav/api/class-rotdrop.bav.BAV.html#_isValidAccount):
This method validates an account against the bank of the last `isValidBank()` call.

* [`BAV::getValidBankFilterCallback()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.BAV.html#_getValidBankFilterCallback):
Returns a callback for filter bank validation.

* [`BAV::getValidAccountFilterCallback()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.BAV.html#_getValidAccountFilterCallback):
Returns a callback for filter account validation. The account filter needs
to be called after the bank filter.

* [`BAV::getMainAgency()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.BAV.html#_getMainAgency):
Returns the main agency of a bank.

* [`BAV::getAgencies()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.BAV.html#_getAgencies):
Returns further agencies. The main agency is not included in this list.
This list can be empty.

An [`Agency`](http://bav-php.github.io/bav/api/class-rotdrop.bav.Agency.html)
object has the fields:

* [`Agency::getBIC()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.Agency.html#_getBIC)

* [`Agency::getPostcode()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.Agency.html#_getPostcode)

* [`Agency::getCity()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.Agency.html#_getCity)

* [`Agency::getName()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.Agency.html#_getName)

* [`Agency::getShortTerm()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.Agency.html#_getShortTerm)

* [`Agency::getPAN()`](http://bav-php.github.io/bav/api/class-rotdrop.bav.Agency.html#_getPAN)

## Example

```php
use rotdrop\bav\BAV;

$bav = new BAV();
$bankID  = "10000000";
$account = "1234567890"

// check for a bank
var_dump(
    $bav->isValidBank($bankID)
);

// check for a bank account
var_dump(
    $bav->isValidBankAccount($bankID, $account)
);

// filter validation
var_dump(
    filter_var($bankID, FILTER_CALLBACK, $bav->getValidBankFilterCallback()),
    filter_var($account, FILTER_CALLBACK, $bav->getValidAccountFilterCallback())
);

// Get informations about a bank
$agency = $bav->getMainAgency($bankID);
echo "{$agency->getName()} {$agency->getCity()}\n";
```
See also `bav/docs/example.php`.


# Optional Dependencies

You may have:

* **CURL**: If you provide `bav/data/banklist.txt` you don't need CURL.

* **mbstring**: BAV works with unicode encoding. Your PHP must have support compiled
in the `mb_*` functions. If these functions are missing BAV works only with the ISO-8859-15 encoding.

* **PDO**: If you intend to use a DBS you need to use
[`PDODataBackendContainer`](http://bav-php.github.io/bav/api/class-rotdrop.bav.PDODataBackendContainer.html).
`PDODataBackendContainer` needs a `PDO` support compiled in PHP.

* **doctrine/orm**: You can use
[`DoctrineBackendContainer`](http://bav-php.github.io/bav/api/class-rotdrop.bav.DoctrineBackendContainer.html)
with Doctrine ORM **3.7 or later within 3.x**, DBAL 3.8.2 or 4.x, and
`symfony/cache` ^7.4 as a data backend. ORM 2 is no longer supported.

```sh
composer require doctrine/orm:^3.7 symfony/cache:^7.4
```

```php
use rotdrop\bav\Configuration\DefaultConfiguration;
use rotdrop\bav\DataBackend\Orm\Doctrine\DoctrineBackendContainer;

$configuration = new DefaultConfiguration();
$configuration->setDataBackendContainer(DoctrineBackendContainer::buildByConnection([
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'dbname' => 'bav',
    'user' => 'bav',
    'password' => 'your-database-password',
    'charset' => 'utf8mb4',
]));

return $configuration;
```

`buildByConnection()` accepts DBAL connection parameters or an existing
`Doctrine\DBAL\Connection`, not the former `['pdo' => $pdo]` option. You can
also inject an `EntityManagerInterface` into `DoctrineBackendContainer` directly;
configure it with the XML mapping paths from `getXMLMappings()`.
Existing BAV table and column names are unchanged. PHP 8.4+ uses native lazy
objects; PHP 8.3 generates proxy classes in the system temporary directory.
An update replaces all bank data and clears the entity manager's identity map;
use a dedicated entity manager and obtain fresh bank objects after updating.


# Development and tests

The test suite uses PHPUnit 12.5 and requires PHP 8.3 or later with the
`curl`, `dom`, `mbstring`, `pdo_sqlite`, `xml`, and `xmlwriter` extensions.
The library also requires PHP 8.3 or later; consumers installing without
development dependencies do not need PHPUnit.

```sh
composer install
composer test
```

The default `unit` suite runs offline, without a database server or downloaded bank
list. It uses the checked-in account cases and Bundesbank excerpt, including
in-memory SQLite tests for the Doctrine backend. To run one
test or randomize execution order:

```sh
vendor/bin/phpunit --filter ValidatorTest
vendor/bin/phpunit --order-by=random
```

The `integration` suite contains the facade, context, database, download, and
backend installation tests. It needs network access to the Bundesbank and GNU,
PDO MySQL and SQLite support, and a **dedicated, disposable MySQL database**.
These tests install, update, and remove tables and bank data. Prepare the database
and downloaded `data/banklist.txt` before running them:

```sh
export PDO_DSN='mysql:host=localhost;dbname=bav_test'
export PDO_USER='bav_test'
export PDO_PASSWORD='your-test-database-password'
export DBAL_URL='mysql://bav_test:your-test-database-password@localhost/bav_test'
php tests/bin/test-install.php
composer test:integration
```

The facade and backend tests retain their historical bank and agency
expectations, which may differ from today's Bundesbank data.
Doctrine integration tests use `DBAL_URL` (URL-encode special characters in
credentials); PDO tests continue to use `PDO_DSN`, `PDO_USER`, and `PDO_PASSWORD`.

The exhaustive cross-project comparisons are opt-in, require the integration
environment and the optional Kontocheck extension, and can take days:

```sh
NODE_NUMBER=0 NODE_COUNT=2 composer test:cross-project
```

The four unfinished parser tests remain marked incomplete. On PHP 8.4 and later,
the existing implicitly nullable `FileUtil` constructor also reports a
deprecation; this is not a PHPUnit API deprecation.


# License and authors

This project is free and under the WTFPL. So do what ever you want.
But it would be nice to leave a note about the authors.

The author of the original project which gave the idea to this project is
Björn Wilmsmann. Responsable for this project is Markus Malkusch <markus@malkusch.de>.

## Donations

If you like BAV and feel generous donate a few Bitcoins here:
[1335STSwu9hST4vcMRppEPgENMHD2r1REK](bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK)

[![Tests](https://github.com/rotdrop/bav/actions/workflows/tests.yml/badge.svg)](https://github.com/rotdrop/bav/actions/workflows/tests.yml)
