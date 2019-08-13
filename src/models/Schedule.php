<?php namespace professionalweb\payment\models;

use professionalweb\payment\contracts\recurring\RecurringSchedule;

class Schedule implements RecurringSchedule
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $paymentToken;

    /**
     * @var string
     */
    private $accountId;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $email;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var bool
     */
    private $needConfirmation;

    /**
     * @var string
     */
    private $interval;

    /**
     * @var int
     */
    private $period;

    /**
     * @var string
     */
    private $startDate;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * Set payment token
     *
     * @param string $token
     *
     * @return RecurringSchedule
     */
    public function setToken(string $token): RecurringSchedule
    {
        $this->paymentToken = $token;

        return $this;
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
        $this->description = $description;

        return $this;
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
        $this->email = $email;

        return $this;
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
        $this->amount = $amount;

        return $this;
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
        $this->currency = $currency;

        return $this;
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
        $this->accountId = $id;

        return $this;
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
        $this->needConfirmation = $flag;

        return $this;
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
        $this->startDate = $startDate;

        return $this;
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
        $this->paymentQty = $qty;

        return $this;
    }

    /**
     * Process payment daily
     *
     * @return RecurringSchedule
     */
    public function daily(): RecurringSchedule
    {
        return $this->setPeriod(1, 'Day');
    }

    /**
     * Process payment weekly
     *
     * @return RecurringSchedule
     */
    public function weekly(): RecurringSchedule
    {
        return $this->setPeriod(1, 'Week');
    }

    /**
     * Process payment every month
     *
     * @return RecurringSchedule
     */
    public function monthly(): RecurringSchedule
    {
        return $this->setPeriod(1, 'Month');
    }

    /**
     * Process payment every year
     *
     * @return RecurringSchedule
     */
    public function yearly(): RecurringSchedule
    {
        return $this->setPeriod(12, 'Month');
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
        return $this->setPeriod($days, 'Day');
    }

    /**
     * @param int    $period
     * @param string $interval
     *
     * @return Schedule
     */
    public function setPeriod(int $period, string $interval): self
    {
        $this->period = $period;
        $this->interval = $interval;

        return $this;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get schedule id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id ?? '';
    }

    /**
     * Get account id
     *
     * @return string
     */
    public function getAccountId(): string
    {
        return $this->accountId ?? '';
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency ?? '';
    }

    /**
     * Check confirmation needed
     *
     * @return bool
     */
    public function isNeedConfirmation(): bool
    {
        return $this->needConfirmation ?? false;
    }

    /**
     * Get first payment date
     *
     * @return string
     */
    public function getStartDate(): string
    {
        return $this->startDate ?? '';
    }

    /**
     * Get payment interval
     *
     * @return string
     */
    public function getInterval(): string
    {
        return $this->period . ' ' . $this->interval;
    }

    /**
     * Check schedule is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive ?? false;
    }

    /**
     * Get payment amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getPaymentToken(): string
    {
        return $this->paymentToken ?? '';
    }

    /**
     * @return int
     */
    public function getPeriod(): int
    {
        return $this->period ?? 0;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'token'               => $this->getPaymentToken(),
            'accountId'           => $this->getAccountId(),
            'description'         => $this->getDescription(),
            'email'               => $this->getEmail(),
            'amount'              => $this->getAmount(),
            'currency'            => $this->getCurrency(),
            'requireConfirmation' => $this->isNeedConfirmation(),
            'startDate'           => $this->getStartDate(),
            'interval'            => $this->getInterval(),
            'period'              => $this->getPeriod(),
        ];
    }
}