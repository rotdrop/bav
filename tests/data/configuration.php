<?php

namespace rotdrop\bav;

use rotdrop\bav\Configuration\DefaultConfiguration;

$configuration = new DefaultConfiguration();

$configuration->setTempDirectory("test");

return $configuration;
