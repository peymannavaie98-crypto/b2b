<?php
namespace HaminShop\B2BSuite\Application\Quote;

class NegotiateQuoteUseCase {
    public function execute(int $quoteId, string $message): array {
        $order = wc_get_order($quoteId);
        if (!$order) {
            throw new \Exception('پیش‌فاکتور یافت نشد.');
        }

        $userId = get_current_user_id();
        $isCustomer = $order->get_customer_id() === $userId;
        $isSalesRep = current_user_can('manage_b2b_negotiations');

        if (!$isCustomer && !$isSalesRep) {
            throw new \Exception('شما دسترسی مذاکره برای این پیش‌فاکتور را ندارید.');
        }

        // Save message to order notes or custom comments table
        $senderType = $isCustomer ? 'مشتری' : 'فروشنده';
        $order->add_order_note(sprintf('پیام مذاکره از طرف %s: %s', $senderType, $message), $isCustomer, true);

        // Transition State if it's the first message
        if ($order->get_status() === 'wc-quote-pending') {
            $order->update_status('wc-quote-negotiating', 'مذاکره آغاز شد.');
        }

        $order->save();

        do_action('haminshop_quote_negotiation', $quoteId, $message, $userId);

        // AI Chatbot / Auto-Responder logic (Phase 4 placeholder)
        $aiResponse = $this->triggerAIResponder($order, $message);

        return [
            'success' => true,
            'message' => 'پیام با موفقیت ثبت شد.',
            'ai_reply' => $aiResponse
        ];
    }

    private function triggerAIResponder(\WC_Order $order, string $message): ?string {
        // Here we would call OpenAI function calling to interpret intent
        // e.g. if customer asks for discount, check constraints and auto-apply if within bounds.
        // For now, return a mocked response.
        if (strpos($message, 'تخفیف') !== false) {
            return 'سیستم هوشمند: درخواست تخفیف شما ثبت شد. به زودی کارشناس فروش بررسی خواهد کرد.';
        }
        return null;
    }
}
