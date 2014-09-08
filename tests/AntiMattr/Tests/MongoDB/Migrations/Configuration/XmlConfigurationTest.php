<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Configuration;

use AntiMattr\MongoDB\Migrations\Configuration\XmlConfiguration;

class XmlConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration()
    {
        $connection = $this->getConnection();
        $config = new XmlConfiguration($connection);
        $config->load(dirname(__DIR__)."/Resources/fixtures/config.xml");

        return $config;
    }
}
