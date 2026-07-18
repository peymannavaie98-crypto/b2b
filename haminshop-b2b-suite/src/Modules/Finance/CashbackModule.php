<?php
namespace HaminShop\B2BSuite\Modules\Finance;

class CashbackModule {

    public function init(): void {
        // Trigger early payment bonus
        add_action('haminshop_early_payment_bonus', [$this, 'creditCashbackBonus'], 10, 2);

        // Cron job for expiring cashback
        add_action('haminshop_cashback_expire', [$this, 'expireOldCashback']);
    }

    public function creditCashbackBonus(int $customerId, float $bonusAmount): void {
        if ($bonusAmount <= 0) return;

        global $wpdb;
        $table = $wpdb->prefix . 'haminshop_b2b_customers';

        // Cashback expires in 6 months according to scenario 4.5
        $expiryDate = gmdate('Y-m-d H:i:s', strtotime('+6 months'));

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET
                cashback_balance = cashback_balance + %f,
                cashback_expires_at = %s
            WHERE user_id = %d",
            $bonusAmount, $expiryDate, $customerId
        ));

        do_action('haminshop_cashback_credited', $customerId, $bonusAmount);
    }

    public function expireOldCashback(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'haminshop_b2b_customers';
        $now = gmdate('Y-m-d H:i:s');

        // Reset cashback to 0 if expired
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET cashback_balance = 0 WHERE cashback_expires_at < %s AND cashback_balance > 0",
            $now
        ));
    }
}
