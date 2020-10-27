<?php

namespace Crm\GoPayModule\Gateways;

use Crm\GoPayModule\Notification\InvalidGopayResponseException;
use Crm\GoPayModule\Notification\PaymentNotFoundException;
use Crm\PaymentsModule\GatewayFail;
use Crm\PaymentsModule\Gateways\RecurrentPaymentInterface;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use Nette\Database\Table\IRow;
use Nette\Utils\DateTime;
use Tracy\Debugger;

class GoPayRecurrent extends BaseGoPay implements RecurrentPaymentInterface
{
    // Maximal recurrenceDateTo according to documentation:
    // https://help.gopay.com/en/knowledge-base/integration-of-payment-gateway/integration-of-payment-gateway-1/recurring-payments
    // This date is not used (but still mandatory), as we use ON_DEMAND recurrent payments and we handle their management ourselves
    // Current maximal date is 2030-12-31 (defined by GoPay backend)
    private $recurrenceDateTo = '2030-12-31';

    public function setRecurrenceDateTo(string $recurrenceDateTo): void
    {
        $this->recurrenceDateTo = $recurrenceDateTo;
    }

    protected function preparePaymentData(IRow $payment): array
    {
        $data = parent::preparePaymentData($payment);
        $data['purchaseData']['recurrence'] = [
            'recurrence_cycle' => 'ON_DEMAND',
            'recurrence_date_to' => DateTime::from(strtotime($this->recurrenceDateTo))->format('Y-m-d'),
        ];
        return $data;
    }

    public function notification(string $id, ?string $parentId = null): bool
    {
        $this->initialize();

        $gopayMeta = $this->gopayPaymentsRepository->getTable()
            ->where(['transaction_reference' => $id])
            ->limit(1)->fetch();
        if (!$gopayMeta) {
            throw new PaymentNotFoundException('Payment with transaction reference ' . $id . ' not found.');
        }

        $payment = $gopayMeta->payment;

        $request = [
            'transactionReference' => $id,
        ];

        $this->response = $this->gateway->completePurchase($request);

        $data = $this->response->getData();
        if ($data === null) {
            throw new InvalidGopayResponseException('Empty response from gopay for payment with transaction reference ' . $id);
        }
        $this->gopayPaymentsRepository->updatePayment($payment, $this->buildGopayPaymentValues($data));

        $this->paymentLogsRepository->add(
            $this->response->isSuccessful() ? 'OK' : 'ERROR',
            json_encode($data),
            $parentId ? 'gopay-notification-recurrent' : 'gopay-notification',
            $payment->id
        );

        if ($payment->status != PaymentsRepository::STATUS_PAID && in_array($data['state'], [self::STATE_PAID, self::STATE_AUTHORIZED])) {
            if ((boolean) $payment->payment_gateway->is_recurrent) {
                $recurrentPayment = $this->recurrentPaymentsRepository->findByPayment($payment);
                if ($recurrentPayment) {
                    $this->recurrentPaymentsProcessor->processChargedRecurrent(
                        $recurrentPayment,
                        PaymentsRepository::STATUS_PAID,
                        $this->getResultCode(),
                        $this->getResultMessage()
                    );
                } else {
                    $payment = $this->paymentsRepository->updateStatus($payment, PaymentsRepository::STATUS_PAID, true);
                    $this->recurrentPaymentsRepository->createFromPayment(
                        $payment,
                        $id
                    );
                }
            } else {
                $this->paymentsRepository->updateStatus($payment, PaymentsRepository::STATUS_PAID, true);
            }
        }

        if (in_array($data['state'], [self::STATE_CANCELED, self::STATE_TIMEOUTED])) {
            if ((boolean) $payment->payment_gateway->is_recurrent) {
                $recurrentPayment = $this->recurrentPaymentsRepository->findByPayment($payment);
                if ($recurrentPayment) {
                    $this->recurrentPaymentsProcessor->processFailedRecurrent($recurrentPayment, $this->getResultCode(), $this->getResultMessage());
                } else {
                    $this->paymentsRepository->updateStatus($payment, $data['state'] === self::STATE_TIMEOUTED ? PaymentsRepository::STATUS_TIMEOUT : PaymentsRepository::STATUS_FAIL, true);
                }
            } else {
                $this->paymentsRepository->updateStatus($payment, $data['state'] === self::STATE_TIMEOUTED ? PaymentsRepository::STATUS_TIMEOUT : PaymentsRepository::STATUS_FAIL, true);
            }
        }

        return true;
    }

    public function getRecurrentToken()
    {
        if (!isset($_GET['id'])) {
            throw new \Exception('Missing gopay payment id for recurrent');
        }

        return $_GET['id'];
    }

    public function hasRecurrentToken(): bool
    {
        return isset($_GET['id']);
    }

    public function charge($payment, $token): string
    {
        $this->initialize();

        $paymentItems = $payment->related('payment_items');
        $items = $this->prepareItems($paymentItems);
        $description = $this->prepareDescription($paymentItems, $payment);

        $data = [
            'transactionReference' => $token,
            'purchaseData' => [
                'amount' => (int)round($payment->amount * 100),
                'currency' => $this->applicationConfig->get('currency'),
                'order_number' => $payment->variable_symbol,
                'order_description' => $description,
                'items' => $items,
            ],
        ];

        if ($this->eetEnabled) {
            $data['purchaseData']['eet'] = $this->prepareEetItems($paymentItems);
        }

        try {
            $this->response = $this->gateway->recurrence($data);
        } catch (\Exception $exception) {
            Debugger::log($exception);
            throw new GatewayFail($exception->getMessage(), $exception->getCode());
        }

        $this->gopayPaymentsRepository->add($payment, $this->response->getTransactionId(), $this->response->getTransactionReference());
        $this->checkChargeStatus($payment, $this->getResultCode());

        if ($this->response->getData()['state'] === self::STATE_CREATED) {
            return self::CHARGE_PENDING;
        }

        return self::CHARGE_OK;
    }

    public function checkValid($token)
    {
        $this->initialize();
        $statusData = $this->gateway->status(['transactionReference' => $token]);
        $data = $statusData->getData();
        if (isset($data['recurrence'])) {
            $end = DateTime::from($data['recurrence']['recurrence_date_to']);
            return $end > new DateTime();
        }
        return false;
    }

    public function checkExpire($recurrentPayments)
    {
        $this->initialize();

        $result = [];
        foreach ($recurrentPayments as $recurrentPayment) {
            $statusData = $this->gateway->status(['transactionReference' => $recurrentPayment]);
            $data = $statusData->getData();
            if (isset($data['payer']['payment_card'])) {
                $expiration = $data['payer']['payment_card']['card_expiration'];
                $month = substr($expiration, 2, 2);
                $year = "20" . substr($expiration, 0, 2);
                $result[$recurrentPayment] = DateTime::from("$year-$month-01 00:00 next month");
            }
        }
        return $result;
    }

    public function getResultCode()
    {
        $err = $this->getError();
        if ($err) {
            return $err['error_code'];
        }
        // response might not be present if it's already processed payment
        return $this->response->getData()['state'] ?? null;
    }

    public function getResultMessage()
    {
        $err = $this->getError();
        if ($err) {
            if (isset($err['error_name'])) {
                return sprintf("%s: %s", $err['error_name'], $err['message']);
            }
            if (isset($err['error_code'])) {
                return $err['description'];
            }
            return 'FAILED';
        }
        // response might not be present if it's already processed payment
        return $this->response->getData()['state'] ?? null;
    }

    protected function getError(): ?array
    {
        if (isset($this->response) && empty($this->response->getData()['errors'])) {
            return null;
        }
        return reset($this->response->getData()['errors']);
    }
}
