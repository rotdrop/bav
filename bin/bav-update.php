#!/bin/env php
<?php
/**
 * BAV updater for the Bundesbank bank data file.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 * @see DataBackend
 */

namespace malkusch\bav;

require_once $_composer_autoload_path
    ?? (is_file(__DIR__ . "/../vendor/autoload.php")
        ? __DIR__ . "/../vendor/autoload.php"
        : __DIR__ . "/../../../autoload.php");

try {
    ConfigurationRegistry::getConfiguration()->setUpdatePlan(null);
    $bav = new BAV();
    $bav->update();
    echo "Bundesbank file downloaded.\n";

} catch (DataBackendException $error) {
    die("Installation failed: {$error->getMessage()}\n");

}
