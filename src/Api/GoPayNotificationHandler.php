<?php

namespace Crm\GoPayModule\Api;

use Crm\ApiModule\Models\Api\ApiHandler;
use Crm\GoPayModule\Notification\InvalidGopayResponseException;
use Crm\GoPayModule\Notification\PaymentNotFoundException;
use Crm\GoPayModule\Notification\UnhandledStateException;
use Crm\GoPayModule\Repositories\GopayPaymentsRepository;
use Crm\PaymentsModule\Models\GatewayFactory;
use Nette\Http\Response;
use Tomaj\NetteApi\Params\GetInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;
use Tracy\Debugger;

/**
 * Class GoPayNotificationHandler
 *
 * API Handler that GoPay call after payment status change.
 * Url for this API is send from crm when creating payment in go pay
 *   - https://doc.gopay.com/en/#callback
 *
 * @package Crm\PaymentsModule\Api
 */
class GoPayNotificationHandler extends ApiHandler
{
    private $gopayPaymentsRepository;

    private $gatewayFactory;

    public function __construct(
        GopayPaymentsRepository $gopayPaymentsRepository,
        GatewayFactory $gatewayFactory,
    ) {
        $this->gopayPaymentsRepository = $gopayPaymentsRepository;
        $this->gatewayFactory = $gatewayFactory;
    }

    public function params(): array
    {
        return [
            (new GetInputParam('id'))->setRequired(),
            new GetInputParam('parent_id'),
        ];
    }


    public function handle(array $params): ResponseInterface
    {
        $gopayMeta = $this->gopayPaymentsRepository->getTable()
            ->where(['transaction_reference' => $params['id']])
            ->limit(1)->fetch();
        if (!$gopayMeta) {
            Debugger::log('Payment with transaction reference ' . $params['id'] . ' not found.', Debugger::EXCEPTION);
            $response = new JsonApiResponse(Response::S200_OK, ['status' => 'ok']);
            return $response;
        }

        $payment = $gopayMeta['payment'];
        try {
            $gateway = $this->gatewayFactory->getGateway($payment['payment_gateway']['code']);
            $result = $gateway->notification($payment, $params['id'], $params['parent_id'] ?? null);
        } catch (InvalidGopayResponseException $e) {
            Debugger::log($e, Debugger::EXCEPTION);
            $response = new JsonApiResponse(Response::S400_BAD_REQUEST, ['status' => 'invalid_response']);
            return $response;
        } catch (PaymentNotFoundException $e) {
            Debugger::log($e, Debugger::EXCEPTION);
        } catch (UnhandledStateException $e) {
            Debugger::log($e, Debugger::EXCEPTION);
        }

        $response = new JsonApiResponse(Response::S200_OK, ['status' => 'ok']);

        return $response;
    }
}
