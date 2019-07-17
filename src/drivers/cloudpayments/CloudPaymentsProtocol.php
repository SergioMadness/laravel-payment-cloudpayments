<?php namespace professionalweb\payment\drivers\cloudpayments;

use professionalweb\payment\contracts\PayProtocol;
use professionalweb\payment\interfaces\CloudPaymentProtocol;

/**
 * Service to work with cloudpayments API
 * @package professionalweb\payment\drivers\cloudpayments
 */
class CloudPaymentsProtocol implements PayProtocol, CloudPaymentProtocol
{

    /**
     * Get payment URL
     *
     * @param array $params
     *
     * @return string
     */
    public function getPaymentUrl(array $params): string
    {
        // TODO: Implement getPaymentUrl() method.
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
        // TODO: Implement prepareParams() method.
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
        // TODO: Implement validate() method.
    }

    /**
     * Get payment ID
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        // TODO: Implement getPaymentId() method.
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
        // TODO: Implement getNotificationResponse() method.
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
        // TODO: Implement getCheckResponse() method.
    }

    /**
     * Get list of schedules
     *
     * @return array
     */
    public function getScheduleList(): array
    {
        // TODO: Implement getScheduleList() method.
    }

    /**
     * Get schedule by id
     *
     * @param string $id
     *
     * @return array
     */
    public function getSchedule(string $id): array
    {
        // TODO: Implement getSchedule() method.
    }

    /**
     * Create schedule
     *
     * @param array $data
     *
     * @return string
     */
    public function createSchedule(array $data): string
    {
        // TODO: Implement createSchedule() method.
    }

    /**
     * Save schedule
     *
     * @param string $id
     * @param array  $data
     *
     * @return bool
     */
    public function updateSchedule(string $id, array $data): bool
    {
        // TODO: Implement updateSchedule() method.
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
        // TODO: Implement removeSchedule() method.
    }
}