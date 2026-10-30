<?php

namespace malkusch\bav;

use malkusch\bav\Configuration\DefaultConfiguration;

$configuration = new DefaultConfiguration();

$configuration->setTempDirectory("test");

return $configuration;
