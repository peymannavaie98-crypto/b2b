<?php
namespace HaminShop\B2BSuite\Domain\Payment;

class SplitPayment {
    public int $orderId;
    public float $totalAmount;
    public float $cashAmount;
    public float $walletAmount;
    public float $chequeAmount;

    public function validate(): bool {
        // Total of parts must equal total amount
        return abs($this->totalAmount - ($this->cashAmount + $this->walletAmount + $this->chequeAmount)) < 0.01;
    }
}
