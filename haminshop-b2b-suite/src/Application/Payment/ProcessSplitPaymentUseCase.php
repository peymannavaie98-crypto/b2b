<?php
namespace HaminShop\B2BSuite\Application\Payment;

class ProcessSplitPaymentUseCase {
    public function execute(array $paymentData): bool {
        // Delegate to SplitPayment Domain entity
        return true;
    }
}
