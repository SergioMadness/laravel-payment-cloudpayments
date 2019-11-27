<?php namespace professionalweb\payment\drivers\cloudpayments;

use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use professionalweb\payment\Form;
use professionalweb\payment\models\Schedule;
use professionalweb\payment\contracts\Receipt;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\contracts\PayProtocol;
use professionalweb\payment\models\PayServiceOption;
use professionalweb\payment\contracts\Form as IForm;
use professionalweb\payment\interfaces\CloudPaymentsService;
use professionalweb\payment\interfaces\CloudPaymentProtocol;
use professionalweb\payment\contracts\recurring\RecurringPayment;
use professionalweb\payment\contracts\recurring\RecurringSchedule;
use professionalweb\payment\contracts\recurring\RecurringPaymentSchedule;

/**
 * CloudPayments implementation
 * @package professionalweb\payment\drivers\cloudpayments
 */
class CloudPaymentsDriver implements PayService, CloudPaymentsService, RecurringPaymentSchedule, RecurringPayment
{

    //<editor-fold desc="Fields">
    /**
     * @var PayProtocol
     */
    private $transport;

    /** @var CloudPaymentProtocol */
    private $cloudPaymentsProtocol;

    /**
     * Notification info
     *
     * @var array
     */
    protected $response;

    /** @var bool */
    private $useWidget;

    /** @var string */
    private $accountId;

    //</editor-fold>

    public function __construct(bool $useWidget = false)
    {
        $this->useWidget($useWidget);
    }

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
        if (!isset($extraParams['token']) && $this->getUseWidget()) {
            return $successReturnUrl;
        }

        if (!isset($extraParams['token']) && !isset($extraParams['checkout'], $extraParams['cardholder_name'])) {
            throw new \Exception('(checkout and cardholder_name) or token params are required');
        }

        $request = [
            'Email'                => $extraParams['email'] ?? null,
            'Amount'               => $amount,
            'Currency'             => $currency,
            'InvoiceId'            => $orderId,
            'Description'          => $description,
            'AccountId'            => $extraParams['user_id'] ?? null,
            'Name'                 => $extraParams['cardholder_name'] ?? null,
            'CardCryptogramPacket' => $extraParams['checkout'] ?? null,
            'IpAddress'            => $extraParams['ip'] ?? ($_SERVER['HTTP_CLIENT_IP'] ?? null),
            'JsonData'             => array_merge($extraParams, ['PaymentId' => $paymentId]),
            'Token'                => $extraParams['token'] ?? null,
        ];

        $paymentUrl = isset($extraParams['token']) ?
            $this->getCloudPaymentsProtocol()->paymentByToken($request) :
            $this->getTransport()->getPaymentUrl($request);

        if (isset($extraParams['token'])) {
            return $successReturnUrl;
        }

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
        return $this->getUseWidget();
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
                                   Receipt $receipt = null): IForm
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
        return $this->getResponseParam('Model.InvoiceId', $this->getResponseParam('InvoiceId'));
    }

    /**
     * Get payment id
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        $data = $this->getResponseParam('Model.JsonData', $this->getResponseParam('Data', []));
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return $data['PaymentId'] ?? '';
    }

    /**
     * Get operation status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getResponseParam('Model.Status', $this->getResponseParam('Status'));
    }

    /**
     * Is payment succeed
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->getResponseParam('Success', true);
    }

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->getResponseParam('Model.TransactionId', $this->getResponseParam('TransactionId'));
    }

    /**
     * Get transaction amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->getResponseParam('Model.Amount', $this->getResponseParam('Amount'));
    }

    /**
     * Get error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->getResponseParam('Model.Message', '');
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
        return $this->getResponseParam('Model.CardFirstSix', $this->getResponseParam('CardFirstSix')) . '******' . $this->getResponseParam('Model.CardLastFour', $this->getResponseParam('CardLastFour'));
    }

    /**
     * Get payment datetime
     *
     * @return string
     */
    public function getDateTime(): string
    {
        return $this->getResponseParam('Model.CreatedDateIso', $this->getResponseParam('CreatedDateIso'));
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
     * @param CloudPaymentProtocol $protocol
     *
     * @return CloudPaymentsDriver
     */
    public function setCloudPaymentsProtocol(CloudPaymentProtocol $protocol): self
    {
        $this->cloudPaymentsProtocol = $protocol;

        return $this->setTransport($protocol);
    }

    /**
     * @return CloudPaymentProtocol
     */
    public function getCloudPaymentsProtocol(): CloudPaymentProtocol
    {
        return $this->cloudPaymentsProtocol;
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
        return response(
            $this->getTransport()->getNotificationResponse($this->response, $this->mapError($errorCode ?? $this->getLastError())),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
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
        return response(
            $this->getTransport()->getNotificationResponse($this->response, $this->mapError($errorCode ?? $this->getLastError())),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Get specific error code
     *
     * @param int $error
     *
     * @return int
     */
    protected function mapError(int $error): int
    {
        $map = [
            self::RESPONSE_SUCCESS             => 0,
            self::RESPONSE_ERROR               => 13,
            self::RESPONSE_ERROR_WRONG_ORDER   => 10,
            self::RESPONSE_ERROR_WRONG_PAYMENT => 10,
            self::RESPONSE_ERROR_WRONG_AMOUNT  => 12,
        ];

        return $map[$error] ?? $map[self::RESPONSE_ERROR];
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
    public static function getOptions(): array
    {
        return [
            (new PayServiceOption())->setType(PayServiceOption::TYPE_BOOL)->setLabel('Is widget')->setAlias('use_widget'),
            (new PayServiceOption())->setType(PayServiceOption::TYPE_STRING)->setLabel('Public Id')->setAlias('publicKey'),
            (new PayServiceOption())->setType(PayServiceOption::TYPE_STRING)->setLabel('Secret key')->setAlias('secretKey'),
        ];
    }

    /**
     * Create schedule
     *
     * @return RecurringSchedule|Schedule
     */
    public function schedule(): RecurringSchedule
    {
        return new Schedule();
    }

    /**
     * Create schedule.
     *
     * @param RecurringSchedule $schedule
     *
     * @return string Schedule id/token
     */
    public function saveSchedule(RecurringSchedule $schedule): string
    {
        if (!empty($schedule->getId())) {
            $this->getCloudPaymentsProtocol()->updateSchedule($schedule->getId(), $schedule->toArray());
        } else {
            $this->getCloudPaymentsProtocol()->createSchedule($schedule->toArray());
        }

        return $schedule->getId();
    }

    /**
     * Remove schedule
     *
     * @param string $token
     *
     * @return bool
     */
    public function removeSchedule(string $token): bool
    {
        return $this->getCloudPaymentsProtocol()->removeSchedule($token);
    }

    /**
     * Get schedule by id
     *
     * @param string $id
     *
     * @return RecurringSchedule
     */
    public function getSchedule(string $id): RecurringSchedule
    {
        $data = $this->getCloudPaymentsProtocol()->getSchedule($id);

        return $this->fillSchedule($data['Model']);
    }

    /**
     * Create and fill schedule
     *
     * @param array $data
     *
     * @return RecurringSchedule
     */
    protected function fillSchedule(array $data): RecurringSchedule
    {
        return $this->schedule()
            ->setPeriod($data['Period'], $data['Interval'])
            ->setId($data['Id'])
            ->setMaxPayments((int)$data['MaxPeriods'])
            ->setAccountId($data['AccountId'])
            ->setDescription($data['Description'])
            ->setEmail($data['Email'])
            ->setAmount($data['Amount'])
            ->setCurrency($data['Currency'])
            ->needConfirmation($data['RequireConfirmation'])
            ->setStartDate($data['StartDateIso']);
    }

    /**
     * Get list of schedules
     *
     * @param string|null $accountId
     *
     * @return array|[]RecurringSchedule
     */
    public function getAllSchedules(string $accountId = null): array
    {
        return array_map(function (array $item) {
            return $this->fillSchedule($item['Model']);
        }, $this->getCloudPaymentsProtocol()->getScheduleList($accountId));
    }

    /**
     * @return bool
     */
    public function getUseWidget(): bool
    {
        return $this->useWidget;
    }

    /**
     * @param mixed $useWidget
     *
     * @return CloudPaymentsDriver
     */
    public function useWidget(bool $useWidget): self
    {
        $this->useWidget = $useWidget;

        return $this;
    }

    /**
     * Get payment token
     *
     * @return string
     */
    public function getRecurringPayment(): string
    {
        return $this->getResponseParam('Token', $this->getResponseParam('Model.Token'));
    }

    /**
     * Initialize recurring payment
     *
     * @param string $token
     * @param string $accountId
     * @param string $paymentId
     * @param string $orderId
     * @param float  $amount
     * @param string $description
     * @param string $currency
     * @param array  $extraParams
     *
     * @return bool
     */
    public function initPayment(string $token, string $orderId, string $paymentId, float $amount, string $description, string $currency = PayService::CURRENCY_RUR_ISO, array $extraParams = []): bool
    {
        $this->getCloudPaymentsProtocol()->paymentByToken([
            'Amount'      => $amount,
            'Currency'    => $currency,
            'Token'       => $token,
            'AccountId'   => $this->getUserId(),
            'Description' => $description,
            'InvoiceId'   => $orderId,
            'Email'       => $extraParams['email'] ?? null,
            'JsonData'    => array_merge($extraParams, [
                'PaymentId' => $paymentId,
            ]),
        ]);

        return true;
    }

    /**
     * Remember payment fo recurring payments
     *
     * @return RecurringPayment
     */
    public function makeRecurring(): RecurringPayment
    {
        return $this;
    }

    /**
     * Set user id payment will be assigned
     *
     * @param string $id
     *
     * @return RecurringPayment
     */
    public function setUserId(string $id): RecurringPayment
    {
        $this->accountId = $id;

        return $this;
    }

    /**
     * Get user id
     *
     * @return string
     */
    public function getUserId(): string
    {
        return (string)$this->accountId;
    }
}