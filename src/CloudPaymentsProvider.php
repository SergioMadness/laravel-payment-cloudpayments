<?php namespace professionalweb\payment;

use CloudPayments\Manager;
use Illuminate\Support\ServiceProvider;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\contracts\PaymentFacade;
use professionalweb\payment\interfaces\CloudPaymentsService;
use professionalweb\payment\drivers\cloudpayments\CloudPaymentsDriver;
use professionalweb\payment\drivers\cloudpayments\CloudPaymentsProtocol;

/**
 * CloudPayments payment provider
 * @package professionalweb\payment
 */
class CloudPaymentsProvider extends ServiceProvider
{

    public function boot(): void
    {
        app(PaymentFacade::class)->registerDriver(CloudPaymentsService::PAYMENT_CLOUDPAYMENTS, CloudPaymentsService::class);
    }


    /**
     * Bind two classes
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(CloudPaymentsService::class, function ($app) {
            return (new CloudPaymentsDriver(config('payment.cloudpayments.use_widget', false)))->setCloudPaymentsProtocol(
                new CloudPaymentsProtocol(
//                    app(Manager::class),
                    config('payment.cloudpayments.url'),
                    config('payment.cloudpayments.publicKey'),
                    config('payment.cloudpayments.secretKey')
                )
            );
        });
        $this->app->bind(PayService::class, function ($app) {
            return (new CloudPaymentsDriver(config('payment.cloudpayments.use_widget', false)))->setCloudPaymentsProtocol(
                new CloudPaymentsProtocol(
//                    app(Manager::class),
                    config('payment.cloudpayments.url'),
                    config('payment.cloudpayments.publicKey'),
                    config('payment.cloudpayments.secretKey')
                )
            );
        });
        $this->app->bind(CloudPaymentsDriver::class, function ($app) {
            return (new CloudPaymentsDriver(config('payment.cloudpayments.use_widget', false)))->setCloudPaymentsProtocol(
                new CloudPaymentsProtocol(
//                    app(Manager::class),
                    config('payment.cloudpayments.url'),
                    config('payment.cloudpayments.publicKey'),
                    config('payment.cloudpayments.secretKey')
                )
            );
        });
        $this->app->bind('\professionalweb\payment\CloudPayments', function ($app) {
            return (new CloudPaymentsDriver(config('payment.cloudpayments.use_widget', false)))->setCloudPaymentsProtocol(
                new CloudPaymentsProtocol(
//                    app(Manager::class),
                    config('payment.cloudpayments.url'),
                    config('payment.cloudpayments.publicKey'),
                    config('payment.cloudpayments.secretKey')
                )
            );
        });
    }
}