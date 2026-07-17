<?php
namespace HaminShop\B2BSuite\Infrastructure\External;

class SayyadAPI {
    // Mocking the Sayyad API for Cheque Inquiry
    public function inquiry(string $sayyadId): string {
        // Implement Circuit Breaker pattern internally here

        // Mocking: return status based on last digit of ID
        $lastDigit = substr($sayyadId, -1);

        if ($lastDigit === '0') {
            return 'rejected'; // Red
        }

        return 'approved'; // White/Green
    }
}
