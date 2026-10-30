#!/bin/env php
<?php
/**
 * Removes the Bundesbank file.
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
    $configuration = ConfigurationRegistry::getConfiguration();
    $configuration->setAutomaticInstallation(false);
    $configuration->setUpdatePlan(null);

    $databack = $configuration->getDatabackendContainer()->getDataBackend();
    $databack->uninstall();
    echo "Bundesbank file removed.\n";

} catch (DataBackendException $error) {
    die("Deinstallation failed: {$error->getMessage()}\n");

}
