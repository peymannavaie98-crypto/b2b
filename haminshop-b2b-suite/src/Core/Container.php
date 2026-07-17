<?php
namespace HaminShop\B2BSuite\Core;

use Psr\Container\ContainerInterface;
use Exception;

class Container implements ContainerInterface {
    private array $services = [];
    private array $instances = [];

    public function get(string $id) {
        if ($this->has($id)) {
            if (!isset($this->instances[$id])) {
                $this->instances[$id] = $this->services[$id]($this);
            }
            return $this->instances[$id];
        }

        throw new Exception("Service not found: {$id}");
    }

    public function has(string $id): bool {
        return isset($this->services[$id]) || isset($this->instances[$id]);
    }

    public function set(string $id, callable $factory): void {
        $this->services[$id] = $factory;
    }
}
