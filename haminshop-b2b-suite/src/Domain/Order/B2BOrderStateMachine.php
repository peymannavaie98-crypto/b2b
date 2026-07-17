<?php
namespace HaminShop\B2BSuite\Domain\Order;

class B2BOrderStateMachine {

    private array $allowedTransitions = [
        'wc-quote-draft' => ['wc-quote-pending'],
        'wc-quote-pending' => ['wc-quote-negotiating', 'wc-quote-approved', 'wc-quote-rejected'],
        'wc-quote-negotiating' => ['wc-quote-approved', 'wc-quote-rejected'],
        'wc-quote-approved' => ['wc-pending-financial', 'wc-quote-expired'],
        'wc-pending-financial' => ['wc-cheque-verifying', 'wc-processing'],
        'wc-cheque-verifying' => ['wc-processing', 'wc-cheque-rejected'],
        'wc-processing' => ['wc-packing'],
        'wc-packing' => ['wc-ready-to-ship'],
        'wc-ready-to-ship' => ['wc-shipped'],
        'wc-shipped' => ['wc-completed'],
        'wc-completed' => ['wc-rma-requested'],
        'wc-rma-requested' => ['wc-rma-approved', 'wc-rma-rejected']
    ];

    public function canTransition(string $currentStatus, string $newStatus): bool {
        if (!isset($this->allowedTransitions[$currentStatus])) {
            return false;
        }
        return in_array($newStatus, $this->allowedTransitions[$currentStatus]);
    }
}
