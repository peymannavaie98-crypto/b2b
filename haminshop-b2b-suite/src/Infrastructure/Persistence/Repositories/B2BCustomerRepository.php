<?php
namespace HaminShop\B2BSuite\Infrastructure\Persistence\Repositories;

use HaminShop\B2BSuite\Domain\Customer\B2BCustomer;

class B2BCustomerRepository {

    public function findById(int $id): ?B2BCustomer {
        global $wpdb;
        $table_name = $wpdb->prefix . 'haminshop_b2b_customers';

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id), ARRAY_A);

        if (!$row) return null;

        $customer = new B2BCustomer();
        $customer->id = (int)$row['id'];
        $customer->userId = (int)$row['user_id'];
        $customer->status = $row['status'];
        // Hydrate other fields...

        return $customer;
    }

    public function save(B2BCustomer $customer): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'haminshop_b2b_customers';

        // Use $wpdb->insert or $wpdb->update depending on $customer->id
    }
}
