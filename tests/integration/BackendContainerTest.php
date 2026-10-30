<?php

namespace malkusch\bav;

use malkusch\bav\Configuration\ConfigurationRegistry;
use malkusch\bav\DataBackend\File\FileDataBackendContainer;
use malkusch\bav\DataBackend\Update\AutomaticUpdatePlan;
use malkusch\bav\Util\FileUtil;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests DataBackendContainer
 *
 * @license WTFPL
 * @author Markus Malkusch <markus@malkusch.de>
 */
class BackendContainerTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Tests automatic installation.
     */
    public function testAutomaticInstallation()
    {
        ConfigurationRegistry::getConfiguration()->setUpdatePlan(null);
        $fileUtil = new FileUtil();
        $container = new FileDataBackendContainer(tempnam($fileUtil->getTempDirectory(), 'bavtest'));

        $this->assertTrue(ConfigurationRegistry::getConfiguration()->isAutomaticInstallation());
    
        $backend = $container->getDataBackend();
        $this->assertTrue($backend->isInstalled());

        $backend->uninstall();
    }

    /**
     * Tests automatic installation.
     */
    public function testAutomaticUpdate()
    {
        $updatePlan = new AutomaticUpdatePlan();
        $updatePlan->setNotice(false);
        ConfigurationRegistry::getConfiguration()->setUpdatePlan($updatePlan);

        $fileUtil = new FileUtil();
        $container = new FileDataBackendContainer(tempnam($fileUtil->getTempDirectory(), 'bavtest'));
        $backend = $container->getDataBackend();
            
        touch($backend->getFile(), strtotime("-1 year"));
        $this->assertTrue($updatePlan->isOutdated($backend));

        $container->applyUpdatePlan($backend);
        $this->assertFalse($updatePlan->isOutdated($backend));

        $backend->uninstall();
        ConfigurationRegistry::getConfiguration()->setUpdatePlan(null);
    }
}
