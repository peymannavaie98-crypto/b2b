<?php
namespace HaminShop\B2BSuite\Application\Customer;

use HaminShop\B2BSuite\Domain\Customer\B2BCustomer;

class CalculateRFMUseCase {
    public function execute(int $customerId): void {
        // Domain logic to calculate Recency, Frequency, Monetary value
        // Applies behavioural penalties (e.g. bounced cheques)
        // Dispatches domain event: haminshop_rfm_grade_changed
    }
}
