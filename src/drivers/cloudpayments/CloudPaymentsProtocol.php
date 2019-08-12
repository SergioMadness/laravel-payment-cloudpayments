<?php namespace professionalweb\payment\drivers\cloudpayments;

//use CloudPayments\Manager;
use professionalweb\payment\contracts\PayProtocol;
use professionalweb\payment\interfaces\CloudPaymentProtocol;

/**
 * Service to work with cloudpayments API
 * @package professionalweb\payment\drivers\cloudpayments
 */
class CloudPaymentsProtocol implements PayProtocol, CloudPaymentProtocol
{
    private const ENDPOINT_CARD_CHARGE = '/payments/cards/charge';

    private const ENDPOINT_SUBSCRIPTION_LIST = '/subscriptions/find';

    private const ENDPOINT_SUBSCRIPTION = '/subscriptions/get';

    private const ENDPOINT_SUBSCRIPTION_CREATE = '/subscriptions/create';

    private const ENDPOINT_SUBSCRIPTION_UPDATE = '/subscriptions/update';

    private const ENDPOINT_SUBSCRIPTION_CANCEL = '/subscriptions/cancel';

    /** @var string */
    private $url;

    /** @var string */
    private $publicKey;

    /** @var string */
    private $privateKey;

//    /** @var Manager */
//    private $cloudPaymentsService;

    /** @var array */
    private $response = [];

    public function __construct(string $url = '', string $publicKey = '', string $privateKey = '')
    {
        $this->setPublicKey($publicKey)->setPrivateKey($privateKey)
//            ->setCloudPaymentsService($service)
            ->setUrl($url);
    }

    /**
     * Get payment URL
     *
     * @param array $params
     *
     * @return string
     * @throws \Exception
     */
    public function getPaymentUrl(array $params): string
    {
        $result = $this->sendRequest(
            self::ENDPOINT_CARD_CHARGE,
            $this->prepareParams($params)
        );

        if (!$result || !$result['Success']) {
            throw new \Exception($result['Message'] ?? '');
        }

        $this->response = $result;

        return $result && isset($result['Model']['AcsUrl']) ? $result['Model']['AcsUrl'] : '';
    }

    /**
     * Prepare parameters
     *
     * @param array $params
     *
     * @return array
     */
    public function prepareParams(array $params): array
    {
        return $params;
    }

    /**
     * Validate params
     *
     * @param array $params
     *
     * @return bool
     */
    public function validate(array $params): bool
    {
        return true;
    }

    /**
     * Get payment ID
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->response['Model']['Transaction'] ?? '';
    }

    /**
     * Prepare response on notification request
     *
     * @param mixed $requestData
     * @param int   $errorCode
     *
     * @return string
     */
    public function getNotificationResponse($requestData, $errorCode): string
    {
        return json_encode(['code' => $errorCode]);
    }

    /**
     * Prepare response on check request
     *
     * @param array $requestData
     * @param int   $errorCode
     *
     * @return string
     */
    public function getCheckResponse($requestData, $errorCode): string
    {
        return json_encode(['code' => $errorCode]);
    }

    /**
     * Get list of schedules
     *
     * @param string $accountId
     *
     * @return array
     * @throws \Exception
     */
    public function getScheduleList(string $accountId): array
    {
        $result = $this->sendRequest(self::ENDPOINT_SUBSCRIPTION_LIST, [
            'accountId' => $accountId,
        ]);

        if (!$result || !$result['Success']) {
            throw new \Exception($result['Message'] ?? '');
        }

        return $result;
    }

    /**
     * Get schedule by id
     *
     * @param string $id
     *
     * @return array
     * @throws \Exception
     */
    public function getSchedule(string $id): array
    {
        $result = $this->sendRequest(self::ENDPOINT_SUBSCRIPTION, [
            'Id' => $id,
        ]);

        if (!$result || !$result['Success']) {
            throw new \Exception($result['Message'] ?? '');
        }

        return $result;
    }

    /**
     * Create schedule
     *
     * @param array $data
     *
     * @return string
     * @throws \Exception
     */
    public function createSchedule(array $data): string
    {
        $result = $this->sendRequest(self::ENDPOINT_SUBSCRIPTION_CREATE, $data);

        if (!$result || !$result['Success']) {
            throw new \Exception($result['Message'] ?? '');
        }

        return $result['Model']['Id'];
    }

    /**
     * Save schedule
     *
     * @param string $id
     * @param array  $data
     *
     * @return bool
     * @throws \Exception
     */
    public function updateSchedule(string $id, array $data): bool
    {
        $data['Id'] = $id;

        $result = $this->sendRequest(self::ENDPOINT_SUBSCRIPTION_UPDATE, $data);

        return $result && $result['Success'];
    }

    /**
     * Remove schedule by id
     *
     * @param string $id
     *
     * @return bool
     */
    public function removeSchedule(string $id): bool
    {
        $result = $this->sendRequest(self::ENDPOINT_SUBSCRIPTION_CANCEL, ['Id' => $id]);

        return $result && $result['Success'];
    }

    /**
     * Send request to cloudpayments
     *
     * @param string $endpoint
     * @param array  $params
     *
     * @return array
     */
    protected function sendRequest(string $endpoint, array $params = []): array
    {
        $url = rtrim($this->getUrl(), '/') . '/' . ltrim($endpoint, '/');

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_USERPWD, sprintf('%s:%s', $this->getPublicKey(), $this->getPrivateKey()));
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));

        $body = curl_exec($curl);

        return json_decode($body, true);
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     *
     * @return $this
     */
    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @param string $privateKey
     *
     * @return $this
     */
    public function setPrivateKey(string $privateKey): self
    {
        $this->privateKey = $privateKey;

        return $this;
    }
//
//    /**
//     * @return Manager
//     */
//    public function getCloudPaymentsService(): Manager
//    {
//        return $this->cloudPaymentsService;
//    }
//
//    /**
//     * @param Manager $cloudPaymentsService
//     *
//     * @return $this
//     */
//    public function setCloudPaymentsService(Manager $cloudPaymentsService): self
//    {
//        $this->cloudPaymentsService = $cloudPaymentsService;
//
//        return $this;
//    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }
}