<?php
namespace HaminShop\B2BSuite\Modules\Payment;

class PaymentModule {

    public function init(): void {
        add_filter('woocommerce_payment_gateways', [$this, 'registerGateways']);
    }

    public function registerGateways($gateways) {
        $gateways[] = ChequeGateway::class;
        return $gateways;
    }
}
