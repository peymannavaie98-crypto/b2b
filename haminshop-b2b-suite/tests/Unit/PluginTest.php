<?php

use PHPUnit\Framework\TestCase;
use HaminShop\B2BSuite\Core\Plugin;

class PluginTest extends TestCase {
    public function test_plugin_is_singleton() {
        $instance1 = Plugin::getInstance();
        $instance2 = Plugin::getInstance();

        $this->assertSame($instance1, $instance2);
    }
}
