<?php namespace professionalweb\payment\drivers\cloudpayments;

use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use professionalweb\payment\contracts\Form;
use professionalweb\payment\contracts\Receipt;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\contracts\PayProtocol;
use professionalweb\payment\models\PayServiceOption;
use professionalweb\payment\interfaces\CloudPaymentsService;
use professionalweb\payment\contracts\recurring\RecurringSchedule;

/**
 * CloudPayments implementation
 * @package professionalweb\payment\drivers\cloudpayments
 */
class CloudPaymentsDriver implements PayService, CloudPaymentsService, RecurringSchedule
{

    /**
     * @var PayProtocol
     */
    private $transport;

    /**
     * Notification info
     *
     * @var array
     */
    protected $response;

    /**
     * Get name of payment service
     *
     * @return string
     */
    public function getName(): string
    {
        return self::PAYMENT_CLOUDPAYMENTS;
    }

    /**
     * Pay
     *
     * @param mixed   $orderId
     * @param mixed   $paymentId
     * @param float   $amount
     * @param string  $currency
     * @param string  $paymentType
     * @param string  $successReturnUrl
     * @param string  $failReturnUrl
     * @param string  $description
     * @param array   $extraParams
     * @param Receipt $receipt
     *
     * @return string
     * @throws \Exception
     */
    public function getPaymentLink($orderId,
                                   $paymentId,
                                   float $amount,
                                   string $currency = self::CURRENCY_RUR,
                                   string $paymentType = self::PAYMENT_TYPE_CARD,
                                   string $successReturnUrl = '',
                                   string $failReturnUrl = '',
                                   string $description = '',
                                   array $extraParams = [],
                                   Receipt $receipt = null): string
    {
        if (!isset($extraParams['checkout'], $extraParams['cardholder_name'])) {
            throw new \Exception('checkout and cardholder_name params are required');
        }

        $request = [
            'Amount'               => $amount,
            'Currency'             => $currency,
            'InvoiceId'            => $orderId,
            'Description'          => $description,
            'AccountId'            => $this->getAccountId(),
            'Name'                 => $extraParams['cardholder_name'],
            'CardCryptogramPacket' => $extraParams['checkout'],
            'IpAddress'            => $extraParams['ip'] ?? ($_SERVER['HTTP_CLIENT_IP'] ?? ''),
            'JsonData'             => array_merge($extraParams, ['PaymentId' => $paymentId]),
        ];

        $paymentUrl = $this->getTransport()->getPaymentUrl($request);

        return $paymentUrl;
    }

    /**
     * Payment system need form
     * You can not get url for redirect
     *
     * @return bool
     */
    public function needForm(): bool
    {
        return false;
    }

    /**
     * Generate payment form
     *
     * @param mixed   $orderId
     * @param mixed   $paymentId
     * @param float   $amount
     * @param string  $currency
     * @param string  $paymentType
     * @param string  $successReturnUrl
     * @param string  $failReturnUrl
     * @param string  $description
     * @param array   $extraParams
     * @param Receipt $receipt
     *
     * @return Form
     */
    public function getPaymentForm($orderId,
                                   $paymentId,
                                   float $amount,
                                   string $currency = self::CURRENCY_RUR,
                                   string $paymentType = self::PAYMENT_TYPE_CARD,
                                   string $successReturnUrl = '',
                                   string $failReturnUrl = '',
                                   string $description = '',
                                   array $extraParams = [],
                                   Receipt $receipt = null): Form
    {
        return new Form();
    }

    /**
     * Validate request
     *
     * @param array $data
     *
     * @return bool
     */
    public function validate(array $data): bool
    {
        return true;
    }

    /**
     * Parse notification
     *
     * @param array $data
     *
     * @return $this
     */
    public function setResponse(array $data): PayService
    {
        $this->response = $data;

        return $this;
    }

    /**
     * Get response param by name
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed|string
     */
    public function getResponseParam(string $name, $default = '')
    {
        return Arr::get($this->response, $name, $default);
    }

    /**
     * Get order ID
     *
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->getResponseParam('Model.InvoiceId');
    }

    /**
     * Get payment id
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->getResponseParam('Model.JsonData.PaymentId');
    }

    /**
     * Get operation status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getResponseParam('Model.Status');
    }

    /**
     * Is payment succeed
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->getResponseParam('Success', false);
    }

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->getResponseParam('Model.TransactionId');
    }

    /**
     * Get transaction amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->getResponseParam('Model.Amount');
    }

    /**
     * Get error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->getResponseParam('Model.Message');
    }

    /**
     * Get payment provider
     *
     * @return string
     */
    public function getProvider(): string
    {
        return self::PAYMENT_TYPE_CARD;
    }

    /**
     * Get PAN
     *
     * @return string
     */
    public function getPan(): string
    {
        return $this->getResponseParam('Model.CardFirstSix') . '******' . $this->getResponseParam('Model.CardLastFour');
    }

    /**
     * Get payment datetime
     *
     * @return string
     */
    public function getDateTime(): string
    {
        return $this->getResponseParam('Model.CreatedDateIso');
    }

    /**
     * Set transport/protocol wrapper
     *
     * @param PayProtocol $protocol
     *
     * @return $this
     */
    public function setTransport(PayProtocol $protocol): PayService
    {
        $this->transport = $protocol;

        return $this;
    }

    /**
     * Get transport
     *
     * @return PayProtocol
     */
    public function getTransport(): PayProtocol
    {
        return $this->transport;
    }

    /**
     * Prepare response on notification request
     *
     * @param int $errorCode
     *
     * @return Response
     */
    public function getNotificationResponse(int $errorCode = null): Response
    {
        return response($this->getTransport()->getNotificationResponse($this->response, $errorCode));
    }

    /**
     * Prepare response on check request
     *
     * @param int $errorCode
     *
     * @return Response
     */
    public function getCheckResponse(int $errorCode = null): Response
    {
        return response($this->getTransport()->getNotificationResponse($this->response, $errorCode));
    }

    /**
     * Get last error code
     *
     * @return int
     */
    public function getLastError(): int
    {
        return 0;
    }

    /**
     * Get param by name
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParam(string $name)
    {
        return $this->getResponseParam($name);
    }

    /**
     * Get pay service options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return [
            (new PayServiceOption())->setType(PayServiceOption::TYPE_STRING)->setLabel('Merchant Id')->setAlias('merchantId'),
            (new PayServiceOption())->setType(PayServiceOption::TYPE_STRING)->setLabel('Secret key')->setAlias('secretKey'),
        ];
    }

    /**
     * Set payment token
     *
     * @param string $token
     *
     * @return RecurringSchedule
     */
    public function setToken(string $token): RecurringSchedule
    {
        // TODO: Implement setToken() method.
    }

    /**
     * Set payment description
     *
     * @param string $description
     *
     * @return RecurringSchedule
     */
    public function setDescription(string $description): RecurringSchedule
    {
        // TODO: Implement setDescription() method.
    }

    /**
     * Set user's e - mail
     *
     * @param string $email
     *
     * @return RecurringSchedule
     */
    public function setEmail(string $email): RecurringSchedule
    {
        // TODO: Implement setEmail() method.
    }

    /**
     * Set payment amount
     *
     * @param float $amount
     *
     * @return RecurringSchedule
     */
    public function setAmount(float $amount): RecurringSchedule
    {
        // TODO: Implement setAmount() method.
    }

    /**
     * Set payment currency
     *
     * @param string $currency
     *
     * @return RecurringSchedule
     */
    public function setCurrency(string $currency): RecurringSchedule
    {
        // TODO: Implement setCurrency() method.
    }

    /**
     * Set account id
     *
     * @param string $id
     *
     * @return RecurringSchedule
     */
    public function setAccountId(string $id): RecurringSchedule
    {
        // TODO: Implement setAccountId() method.
    }

    /**
     * Set payment need confirmation
     *
     * @param bool $flag
     *
     * @return RecurringSchedule
     */
    public function needConfirmation(bool $flag = true): RecurringSchedule
    {
        // TODO: Implement needConfirmation() method.
    }

    /**
     * Set date of first payment
     *
     * @param string $startDate
     *
     * @return RecurringSchedule
     */
    public function setStartDate(string $startDate): RecurringSchedule
    {
        // TODO: Implement setStartDate() method.
    }

    /**
     * Max payment quantity
     *
     * @param int $qty
     *
     * @return RecurringSchedule
     */
    public function setMaxPayments(int $qty): RecurringSchedule
    {
        // TODO: Implement setMaxPayments() method.
    }

    /**
     * Process payment daily
     *
     * @return RecurringSchedule
     */
    public function daily(): RecurringSchedule
    {
        // TODO: Implement daily() method.
    }

    /**
     * Process payment weekly
     *
     * @return RecurringSchedule
     */
    public function weekly(): RecurringSchedule
    {
        // TODO: Implement weekly() method.
    }

    /**
     * Process payment every month
     *
     * @return RecurringSchedule
     */
    public function monthly(): RecurringSchedule
    {
        // TODO: Implement monthly() method.
    }

    /**
     * Process payment every year
     *
     * @return RecurringSchedule
     */
    public function yearly(): RecurringSchedule
    {
        // TODO: Implement yearly() method.
    }

    /**
     * Process payment every $days days
     *
     * @param int $days
     *
     * @return RecurringSchedule
     */
    public function every(int $days): RecurringSchedule
    {
        // TODO: Implement every() method.
    }

    /**
     * Get schedule id
     *
     * @return string
     */
    public function getId(): string
    {
        // TODO: Implement getId() method.
    }

    /**
     * Get account id
     *
     * @return string
     */
    public function getAccountId(): string
    {
        // TODO: Implement getAccountId() method.
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription(): string
    {
        // TODO: Implement getDescription() method.
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail(): string
    {
        // TODO: Implement getEmail() method.
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency(): string
    {
        // TODO: Implement getCurrency() method.
    }

    /**
     * Check confirmation needed
     *
     * @return bool
     */
    public function isNeedConfirmation(): bool
    {
        // TODO: Implement isNeedConfirmation() method.
    }

    /**
     * Get first payment date
     *
     * @return string
     */
    public function getStartDate(): string
    {
        // TODO: Implement getStartDate() method.
    }

    /**
     * Get payment interval
     *
     * @return string
     */
    public function getInterval(): string
    {
        // TODO: Implement getInterval() method.
    }

    /**
     * Check schedule is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        // TODO: Implement isActive() method.
    }


}