<?php
namespace HaminShop\B2BSuite\Core;

class EventDispatcher {

    /**
     * Dispatch an internal domain event AND a WordPress hook
     */
    public function dispatch(string $eventName, array $payload = []): void {
        // Dispatch WP Action
        $args = array_merge(["haminshop_{$eventName}"], $payload);
        call_user_func_array('do_action', $args);
    }
}
