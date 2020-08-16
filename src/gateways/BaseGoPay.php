<?php

namespace Crm\GoPayModule\Gateways;

use Crm\GoPayModule\Repository\GopayPaymentsRepository;
use Crm\GoPayModule\Repository\GopayPaymentValues;
use Crm\PaymentsModule\Gateways\GatewayAbstract;
use Crm\PaymentsModule\RecurrentPaymentsProcessor;
use Crm\PaymentsModule\Repository\PaymentLogsRepository;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use Crm\PaymentsModule\Repository\RecurrentPaymentsRepository;
use Nette\Database\Table\IRow;
use Omnipay\GoPay\Gateway;
use Omnipay\Omnipay;
use Crm\ApplicationModule\Config\ApplicationConfig;
use Nette\Application\LinkGenerator;
use Nette\Http\Response;
use Nette\Localization\ITranslator;
use League\Event\Emitter;

abstract class BaseGoPay extends GatewayAbstract
{
    // https://doc.gopay.com/cs/#stavy-plateb
    const STATE_PAID = 'PAID';
    const STATE_CREATED = 'CREATED';
    const STATE_CANCELED = 'CANCELED';
    const STATE_TIMEOUTED = 'TIMEOUTED';
    const STATE_AUTHORIZED = 'AUTHORIZED';

    // https://doc.gopay.com/en/#payment-substate
    const PENDING_PAYMENT_SUB_STATE = ['_101', '_102'];

    /** @var Gateway */
    protected $gateway;

    protected $gopayPaymentsRepository;

    protected $paymentsRepository;

    protected $recurrentPaymentsRepository;

    protected $recurrentPaymentsProcessor;

    protected $eventEmitter;

    protected $paymentLogsRepository;

    protected $eetEnabled = false;

    public function __construct(
        LinkGenerator $linkGenerator,
        ApplicationConfig $applicationConfig,
        Response $httpResponse,
        ITranslator $translator,
        GopayPaymentsRepository $gopayPaymentsRepository,
        PaymentsRepository $paymentsRepository,
        RecurrentPaymentsProcessor $recurrentPaymentsProcessor,
        PaymentLogsRepository $paymentLogsRepository,
        RecurrentPaymentsRepository $recurrentPaymentsRepository,
        Emitter $eventEmitter
    ) {
        parent::__construct($linkGenerator, $applicationConfig, $httpResponse, $translator);
        $this->paymentsRepository = $paymentsRepository;
        $this->recurrentPaymentsProcessor = $recurrentPaymentsProcessor;
        $this->eventEmitter = $eventEmitter;
        $this->gopayPaymentsRepository = $gopayPaymentsRepository;
        $this->paymentLogsRepository = $paymentLogsRepository;
        $this->recurrentPaymentsRepository = $recurrentPaymentsRepository;
    }

    protected function initialize()
    {
        $this->gateway = Omnipay::create('GoPay');

        $this->gateway->initialize([
            'goId' => $this->applicationConfig->get('gopay_go_id'),
            'clientId' => $this->applicationConfig->get('gopay_client_id'),
            'clientSecret' => $this->applicationConfig->get('gopay_client_secret'),
            'testMode' => $this->applicationConfig->get('gopay_test_mode'),
        ]);

        if ($this->applicationConfig->get('gopay_eet_enabled')) {
            $this->eetEnabled = true;
        }
    }

    public function begin($payment)
    {
        $this->initialize();

        $goPayOrder = $this->preparePaymentData($payment);

        $this->response = $this->gateway->purchase($goPayOrder);

        if ($this->response->isSuccessful()) {
            $this->gopayPaymentsRepository->add($payment, $this->response->getTransactionId(), $this->response->getTransactionReference());
        }
    }

    public function complete($payment): ?bool
    {
        $this->initialize();

        $gopayMeta = $this->gopayPaymentsRepository->findByPayment($payment);
        if (!isset($gopayMeta->transaction_reference)) {
            throw new \Exception('Cannot find gopay_transaction_reference for payment - ' . $payment->id);
        }

        $request = [
            'transactionReference' => $gopayMeta->transaction_reference,
        ];

        $this->response = $this->gateway->completePurchase($request);

        $data = $this->response->getData();
        $this->gopayPaymentsRepository->updatePayment($payment, $this->buildGopayPaymentValues($data));

        if (isset($data['sub_state']) && in_array($data['sub_state'], self::PENDING_PAYMENT_SUB_STATE)) {
            return null;
        }

        return $data['state'] == self::STATE_PAID;
    }

    protected function handleSuccessful($response)
    {
        header('Location: ' . $response->getRedirectUrl());
        exit;
    }

    protected function buildGopayPaymentValues(array $data): GopayPaymentValues
    {
        $values = new GopayPaymentValues();
        $values->setState($data['state']);

        if (isset($data['sub_state'])) {
            $values->setSubState($data['sub_state']);
        }
        if (isset($data['payment_instrument'])) {
            $values->setPaymentInstrument($data['payment_instrument']);
        }
        if (isset($data['payer']['payment_card'])) {
            $values->setCardNumber($data['payer']['payment_card']['card_number'] ?? null)
                ->setCardExpiration($data['payer']['payment_card']['card_expiration'] ?? null)
                ->setCardBrand($data['payer']['payment_card']['card_brand'] ?? null)
                ->setIssuerCountry($data['payer']['payment_card']['card_issuer_country'] ?? null)
                ->setIssuerBank($data['payer']['payment_card']['card_issuer_bank'] ?? null);
        }
        if (isset($data['payer']['bank_account'])) {
            $values->setAccountNumber($data['payer']['bank_account']['account_number'] ?? null)
                ->setBankCode($data['payer']['bank_account']['bank_code'] ?? null)
                ->setAccountName($data['payer']['bank_account']['account_name'] ?? null);
        }
        if (isset($data['payer']['contact']['email'])) {
            $values->setContactEmail($data['payer']['contact']['email']);
        }
        if (isset($data['payer']['contact']['country_code'])) {
            $values->setContactCountryCode($data['payer']['contact']['country_code']);
        }
        $values->setUrl($data['gw_url']);
        if (isset($data['recurrence'])) {
            $values->setRecurrenceCycle($data['recurrence']['recurrence_cycle'] ?? null)
                ->setRecurrenceDateTo($data['recurrence']['recurrence_date_to'] ?? null)
                ->setRecurrenceState($data['recurrence']['recurrence_state'] ?? null);
        }
        if (isset($data['eet_code'])) {
            $values->setEetFik($data['eet_code']['fik'] ?? null)
                ->setEetBkp($data['eet_code']['bkp'] ?? null)
                ->setEetPkp($data['eet_code']['pkp'] ?? null);
        }
        return $values;
    }

    protected function preparePaymentData(IRow $payment): array
    {
        $returnUrl = $this->generateReturnUrl($payment, [
            'vs' => $payment->variable_symbol,
        ]);

        $notifyUrl = $this->linkGenerator->link(
            'Api:Api:api',
            [
                'version' => 1,
                'category' => 'gopay',
                'apiaction' => 'notification',
            ]
        );

        $paymentItems = $payment->related('payment_items');
        $items = $this->prepareItems($paymentItems);
        $description = $this->prepareDescription($paymentItems, $payment);

        $data = [
            'purchaseData' => [
                'payer' => [
                    'default_payment_instrument' => 'PAYMENT_CARD',
                ],
                'target' => [
                    'type' => 'ACCOUNT',
                    'goid' => $this->applicationConfig->get('gopay_go_id'),
                ],
                'amount' => (int) round($payment->amount * 100),
                'currency' => $this->applicationConfig->get('currency'),
                'order_number' => $payment->variable_symbol,
                'order_description' => $description,
                'items' => $items,
                'callback' => [
                    'return_url' => $returnUrl,
                    'notification_url' => $notifyUrl,
                ],
            ],
        ];

        if (isset($payment->user->email)) {
            $data['purchaseData']['payer']['contact']['email'] = $payment->user->email;
        }

        if ($this->eetEnabled) {
            $data['purchaseData']['eet'] = $this->prepareEetItems($paymentItems);
        }

        return $data;
    }

    protected function prepareItems($paymentItems): array
    {
        $items = [];
        foreach ($paymentItems as $paymentItem) {
            $items[] = [
                'count' => $paymentItem->count,
                'name' => $paymentItem->name,
                'amount' => (int) round($paymentItem->amount * $paymentItem->count * 100),
            ];
        }
        return $items;
    }

    protected function prepareEetItems($paymentItems): array
    {
        $eet = [
            'celk_trzba' => 0,
            'mena' => $this->applicationConfig->get('currency'),
        ];
        foreach ($paymentItems as $paymentItem) {
            // EET - see documentation https://doc.gopay.com/cs/#eet
            // we have three fixed VAT level
            $eet['celk_trzba'] += (int)round($paymentItem->amount * $paymentItem->count * 100);
            if ($paymentItem->vat == 21) {
                if (!isset($eet['zakl_dan1'])) {
                    $eet['zakl_dan1'] = 0;
                }
                if (!isset($eet['dan1'])) {
                    $eet['dan1'] = 0;
                }
                $total = $paymentItem->amount * $paymentItem->count;
                $base = (int)round($total / (1 + $paymentItem->vat / 100) * 100);
                $vat = (int)round($total * 100 - $base);
                $eet['zakl_dan1'] += $base;
                $eet['dan1'] += $vat;
            } elseif ($paymentItem->vat == 15) {
                if (!isset($eet['zakl_dan2'])) {
                    $eet['zakl_dan2'] = 0;
                }
                if (!isset($eet['dan2'])) {
                    $eet['dan2'] = 0;
                }
                $total = $paymentItem->amount * $paymentItem->count;
                $base = (int)round($total / (1 + $paymentItem->vat / 100) * 100);
                $vat = (int)round($total * 100 - $base);
                $eet['zakl_dan2'] += $base;
                $eet['dan2'] += $vat;
            } elseif ($paymentItem->vat == 10) {
                if (!isset($eet['zakl_dan3'])) {
                    $eet['zakl_dan3'] = 0;
                }
                if (!isset($eet['dan3'])) {
                    $eet['dan3'] = 0;
                }
                $total = $paymentItem->amount * $paymentItem->count;
                $base = (int)round($total / (1 + $paymentItem->vat / 100) * 100);
                $vat = (int)round($total * 100 - $base);
                $eet['zakl_dan3'] += $base;
                $eet['dan3'] += $vat;
            } elseif ($paymentItem->vat == 0) {
                if (!isset($eet['zakl_nepodl_dph'])) {
                    $eet['zakl_nepodl_dph'] = 0;
                }
                $eet['zakl_nepodl_dph'] += (int)round($paymentItem->amount * $paymentItem->count * 100);
            } else {
                throw new \Exception("Unknown vat rate '{$paymentItem->vat}' for EET reporting");
            }
        }
        return $eet;
    }

    protected function prepareDescription($paymentItems, IRow $payment): string
    {
        if (count($paymentItems) == 1) {
            foreach ($paymentItems as $paymentItem) {
                return $paymentItem->name . ' / ' . $payment->variable_symbol;
            }
        }
        return $this->applicationConfig->get('site_title');
    }
}
