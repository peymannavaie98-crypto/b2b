<?php
namespace HaminShop\B2BSuite\Modules\Marketing;

class RestockModule {

    public function init(): void {
        // Daily cron job via Action Scheduler
        add_action('haminshop_send_restock_reminders', [$this, 'processRestockReminders']);
    }

    public function processRestockReminders(): void {
        global $wpdb;

        // Find orders completed between 40 and 50 days ago (for a 45-day cycle example)
        $dateFrom = gmdate('Y-m-d H:i:s', strtotime('-50 days'));
        $dateTo = gmdate('Y-m-d H:i:s', strtotime('-40 days'));

        $orders = wc_get_orders([
            'status' => 'completed',
            'date_completed' => $dateFrom . '...' . $dateTo,
            'limit' => -1,
            'type' => 'shop_order'
        ]);

        foreach ($orders as $order) {
            // Check if customer is B2B verified
            $customerId = $order->get_customer_id();
            $user = get_userdata($customerId);

            if (!$user || !in_array('b2b_verified', (array) $user->roles)) {
                continue;
            }

            foreach ($order->get_items() as $item) {
                $productId = $item->get_product_id();

                // In a real implementation, ML or historical average days between purchases
                // per product/customer would dictate this. Here we assume 45 days.

                // Has the customer bought this product again recently?
                $recentOrders = wc_get_orders([
                    'customer_id' => $customerId,
                    'status' => ['completed', 'processing'],
                    'date_created' => '>=' . $dateFrom,
                    'limit' => 1
                ]);

                $hasBoughtAgain = false;
                foreach ($recentOrders as $recentOrder) {
                    foreach ($recentOrder->get_items() as $recentItem) {
                        if ($recentItem->get_product_id() === $productId) {
                            $hasBoughtAgain = true;
                            break 2;
                        }
                    }
                }

                if (!$hasBoughtAgain) {
                    $this->sendReminder($customerId, $productId);
                    // Prevent spamming
                    break;
                }
            }
        }
    }

    private function sendReminder(int $customerId, int $productId): void {
        // Mock sending SMS / Email / Panel Notification
        // Dispatch Domain Event
        do_action('haminshop_restock_reminder_sent', $customerId, $productId);

        // Log it for observability
        error_log(sprintf('Restock Reminder sent to Customer ID %d for Product ID %d', $customerId, $productId));
    }
}
