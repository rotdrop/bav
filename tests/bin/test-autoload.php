<?php

namespace rotdrop\bav;

use rotdrop\bav\Bank\Agency;
use rotdrop\bav\Bank\Bank;
use rotdrop\bav\Configuration\Configuration;
use rotdrop\bav\Configuration\ConfigurationRegistry;
use rotdrop\bav\Configuration\DefaultConfiguration;
use rotdrop\bav\DataBackend\File\FileDataBackend;
use rotdrop\bav\DataBackend\File\FileParser\FileParser;
use rotdrop\bav\DataBackend\Orm\Doctrine\DoctrineBackendContainer;
use rotdrop\bav\DataBackend\Orm\MetaData;
use rotdrop\bav\DataBackend\Update\AutomaticUpdatePlan;
use rotdrop\bav\Validator\ValidatorFactory;

require_once __DIR__ . "/../../vendor/autoload.php";

$check = function ($condition, $message) {
    if (! $condition) {
        throw new \RuntimeException($message);
    }
};

$configuration = ConfigurationRegistry::getConfiguration();
$check($configuration instanceof DefaultConfiguration, "Default configuration was not initialized.");
$check(ConfigurationRegistry::getConfiguration() === $configuration, "Configuration was initialized twice.");
$configuration->setAutomaticInstallation(false);
$configuration->setUpdatePlan(null);

$customConfiguration = new Configuration();
ConfigurationRegistry::setConfiguration($customConfiguration);
$check(
    ConfigurationRegistry::getConfiguration() === $customConfiguration,
    "Programmatic configuration was overwritten."
);
ConfigurationRegistry::setConfiguration($configuration);

$sourceDirectory = realpath(__DIR__ . "/../../src");
foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDirectory)) as $entry) {
    if ($entry->getExtension() !== "php") {
        continue;
    }
    $file = $entry->getPathname();
    $relativePath = substr($file, strlen($sourceDirectory) + 1, -4);
    foreach (explode(DIRECTORY_SEPARATOR, $relativePath) as $segment) {
        $check(ctype_upper($segment[0]), "PSR-4 path segment does not start with an uppercase letter: $segment.");
    }
    $class = __NAMESPACE__ . "\\" . str_replace(DIRECTORY_SEPARATOR, "\\", $relativePath);
    $check(class_exists($class) || interface_exists($class), "Could not autoload $class.");
    $reflection = new \ReflectionClass($class);
    $check($reflection->getName() === $class, "Class name does not match the PSR-4 path: $file.");
    $check($reflection->getFileName() === $file, "Class was loaded from the wrong file: $class.");
}
$check(! class_exists(__NAMESPACE__ . "\\MissingAutoloadClass"), "Unknown class unexpectedly exists.");

$parser = new FileParser();
$check(
    realpath(dirname($parser->getFile())) === realpath(__DIR__ . "/../../data"),
    "Default bank data path changed."
);
$check(
    realpath(dirname($sourceDirectory . "/Configuration" . ConfigurationRegistry::BAV_PATH))
        === realpath(__DIR__ . "/../.."),
    "Configuration path does not point to the repository root."
);
foreach (DoctrineBackendContainer::getXMLMappings() as $mappingDirectory) {
    $check(count(glob($mappingDirectory . "*.xml")) === 3, "Doctrine mappings were not found.");
}
if (class_exists(\Doctrine\ORM\Mapping\Driver\XmlDriver::class)) {
    $driver = new \Doctrine\ORM\Mapping\Driver\XmlDriver(DoctrineBackendContainer::getXMLMappings());
    $entities = $driver->getAllClassNames();
    $expectedEntities = array(Bank::class, Agency::class, MetaData::class);
    sort($entities);
    sort($expectedEntities);
    $check($entities === $expectedEntities, "Doctrine did not discover the renamed entities.");
    foreach ($entities as $class) {
        $metadata = new \Doctrine\ORM\Mapping\ClassMetadata($class);
        $driver->loadMetadataForClass($class, $metadata);
        foreach ($metadata->associationMappings as $association) {
            $check(class_exists($association["targetEntity"]), "Doctrine association target was not found.");
        }
    }
}

$backend = new FileDataBackend();
$factory = new ValidatorFactory();
$validatorCount = 0;
foreach (glob($sourceDirectory . "/Validator/Validators/Validator*.php") as $file) {
    if (! preg_match('~^Validator([A-Z0-9]{2})\.php$~', basename($file), $matches)) {
        continue;
    }
    $validator = $factory->build(new Bank($backend, "12345678", $matches[1]));
    $class = __NAMESPACE__ . "\\Validator\\Validators\\Validator" . $matches[1];
    $check(get_class($validator) === $class, "Factory did not load $class.");
    $validatorCount++;
}
$check($validatorCount === 144, "Expected 144 validators, found $validatorCount.");

$accountCases = json_decode(file_get_contents(__DIR__ . "/../data/accounts.json"), true, 512, JSON_THROW_ON_ERROR);
foreach ($accountCases as $case) {
    $bank = new Bank($backend, $case["blz"] ?? "12345678", $case["validator"]);
    foreach (array("valid" => true, "invalid" => false) as $key => $expected) {
        foreach ($case[$key] ?? array() as $account) {
            $check(
                $bank->isValid($account) === $expected,
                "Validator {$case['validator']} returned the wrong result for $account."
            );
        }
    }
}

if (class_exists(TimeMock::class)) {
    $datedBackend = new class extends FileDataBackend {
        public function getLastUpdate()
        {
            return strtotime("2013-01-31");
        }
    };
    try {
        TimeMock::setTime(strtotime("2013-01-31"));
        $check(! (new AutomaticUpdatePlan())->isOutdated($datedBackend), "Update plan ignored the time mock.");
        TimeMock::setTime(strtotime("2013-04-01"));
        $check((new AutomaticUpdatePlan())->isOutdated($datedBackend), "Update plan ignored the mocked update date.");
    } finally {
        TimeMock::disable();
    }
}

$includePath = get_include_path();
try {
    set_include_path($includePath . PATH_SEPARATOR . __DIR__ . "/../data");
    ConfigurationRegistry::classConstructor();
    $check(
        ConfigurationRegistry::getConfiguration()->getTempDirectory() === "test",
        "Include-path configuration was not loaded."
    );
} finally {
    set_include_path($includePath);
    ConfigurationRegistry::setConfiguration($configuration);
}

echo "PSR-4 autoloading checks passed ($validatorCount validators).\n";
