<?php

namespace Crm\GoPayModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\ApiModule\Params\InputParam;
use Crm\ApiModule\Params\ParamsProcessor;
use Crm\GoPayModule\Notification\InvalidGopayResponseException;
use Crm\GoPayModule\Notification\PaymentNotFoundException;
use Crm\GoPayModule\Notification\UnhandledStateException;
use Crm\GoPayModule\Repository\GopayPaymentsRepository;
use Crm\PaymentsModule\GatewayFactory;
use Nette\Http\Response;
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
        GatewayFactory $gatewayFactory
    ) {
        $this->gopayPaymentsRepository = $gopayPaymentsRepository;
        $this->gatewayFactory = $gatewayFactory;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_GET, 'id', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_GET, 'parent_id', InputParam::OPTIONAL),
        ];
    }

    /**
     * @param ApiAuthorizationInterface $authorization
     * @return \Nette\Application\IResponse
     */
    public function handle(ApiAuthorizationInterface $authorization)
    {
        $paramsProcessor = new ParamsProcessor($this->params());
        if ($paramsProcessor->isError()) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'Missing id parameter']);
            $response->setHttpCode(Response::S400_BAD_REQUEST);
            return $response;
        }
        $params = $paramsProcessor->getValues();

        $gopayMeta = $this->gopayPaymentsRepository->getTable()
            ->where(['transaction_reference' => $params['id']])
            ->limit(1)->fetch();
        if (!$gopayMeta) {
            Debugger::log('Payment with transaction reference ' . $params['id'] . ' not found.', Debugger::EXCEPTION);
            $response = new JsonResponse(['status' => 'ok']);
            $response->setHttpCode(Response::S200_OK);
            return $response;
        }

        $payment = $gopayMeta['payment'];
        try {
            $gateway = $this->gatewayFactory->getGateway($payment['payment_gateway']['code']);
            $result = $gateway->notification($payment, $params['id'], $params['parent_id'] ?? null);
        } catch (InvalidGopayResponseException $e) {
            Debugger::log($e, Debugger::EXCEPTION);
            $response = new JsonResponse(['status' => 'invalid_response']);
            $response->setHttpCode(Response::S400_BAD_REQUEST);
            return $response;
        } catch (PaymentNotFoundException $e) {
            Debugger::log($e, Debugger::EXCEPTION);
        } catch (UnhandledStateException $e) {
            Debugger::log($e, Debugger::EXCEPTION);
        }

        $response = new JsonResponse(['status' => 'ok']);
        $response->setHttpCode(Response::S200_OK);

        return $response;
    }
}
