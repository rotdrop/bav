<?php

namespace malkusch\bav;

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
foreach (glob($sourceDirectory . "/*.php") as $file) {
    $class = __NAMESPACE__ . "\\" . basename($file, ".php");
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
    realpath(dirname($sourceDirectory . ConfigurationRegistry::BAV_PATH)) === realpath(__DIR__ . "/../.."),
    "Configuration path does not point to the repository root."
);
foreach (DoctrineBackendContainer::getXMLMappings() as $mappingDirectory) {
    $check(count(glob($mappingDirectory . "*.xml")) === 3, "Doctrine mappings were not found.");
}

$backend = new FileDataBackend();
$factory = new ValidatorFactory();
$validatorCount = 0;
foreach (glob($sourceDirectory . "/Validator*.php") as $file) {
    if (! preg_match('~^Validator([A-Z0-9]{2})\.php$~', basename($file), $matches)) {
        continue;
    }
    $validator = $factory->build(new Bank($backend, "12345678", $matches[1]));
    $class = __NAMESPACE__ . "\\Validator" . $matches[1];
    $check(get_class($validator) === $class, "Factory did not load $class.");
    $validatorCount++;
}
$check($validatorCount > 0, "No validators were discovered.");

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
