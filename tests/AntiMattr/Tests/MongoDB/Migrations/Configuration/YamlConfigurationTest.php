<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Configuration;

use AntiMattr\MongoDB\Migrations\Configuration\YamlConfiguration;

class YamlConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration()
    {
        $connection = $this->getConnection();
        $config = new YamlConfiguration($connection);
        $config->load(dirname(__DIR__)."/Resources/fixtures/config.yml");

        return $config;
    }
}
