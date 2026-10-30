#!/bin/env php
<?php
/**
 * BAV installer for the Bundesbank bank data file.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 * @see DataBackend
 */

namespace rotdrop\bav;

use rotdrop\bav\Configuration\ConfigurationRegistry;
use rotdrop\bav\DataBackend\DataBackend;
use rotdrop\bav\DataBackend\Exception\DataBackendException;

require_once $_composer_autoload_path
    ?? (is_file(__DIR__ . "/../vendor/autoload.php")
        ? __DIR__ . "/../vendor/autoload.php"
        : __DIR__ . "/../../../autoload.php");

try {
    ConfigurationRegistry::getConfiguration()->setAutomaticInstallation(false);
    $databack = ConfigurationRegistry::getConfiguration()->getDatabackendContainer()->getDataBackend();

    $databack->install();
    echo "Bundesbank file downloaded.\n";

} catch (DataBackendException $error) {
    die("Installation failed: {$error->getMessage()}\n");

}
