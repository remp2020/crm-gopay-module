<?php

namespace Crm\GoPayModule;

use Crm\ApiModule\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Authorization\NoAuthorization;
use Crm\ApiModule\Router\ApiIdentifier;
use Crm\ApiModule\Router\ApiRoute;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\SeederManager;
use Crm\GoPayModule\Api\GoPayNotificationHandler;
use Crm\GoPayModule\Seeders\ConfigsSeeder;
use Crm\GoPayModule\Seeders\PaymentGatewaysSeeder;

class GoPayModule extends CrmModule
{
    public function registerApiCalls(ApiRoutersContainerInterface $apiRoutersContainer)
    {
        $apiRoutersContainer->attachRouter(
            new ApiRoute(
                new ApiIdentifier('1', 'gopay', 'notification'),
                GoPayNotificationHandler::class,
                NoAuthorization::class
            )
        );
    }

    public function registerSeeders(SeederManager $seederManager)
    {
        $seederManager->addSeeder($this->getInstance(ConfigsSeeder::class));
        $seederManager->addSeeder($this->getInstance(PaymentGatewaysSeeder::class));
    }
}
