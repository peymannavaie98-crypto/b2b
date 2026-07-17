<?php
namespace HaminShop\B2BSuite\Domain\Customer;

class B2BCustomer {
    public int $id;
    public int $userId;
    public string $businessType;
    public string $status;
    public float $creditLimit;
    public float $walletBalance;
    public string $rfmGrade;

    // Constructors, methods to change status, adjust balances, etc.
}
