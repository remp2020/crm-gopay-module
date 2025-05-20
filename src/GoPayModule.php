<?php

namespace Crm\GoPayModule;

use Crm\ApiModule\Models\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Models\Authorization\NoAuthorization;
use Crm\ApiModule\Models\Router\ApiIdentifier;
use Crm\ApiModule\Models\Router\ApiRoute;
use Crm\ApplicationModule\Application\Managers\SeederManager;
use Crm\ApplicationModule\CrmModule;
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
                NoAuthorization::class,
            ),
        );
    }

    public function registerSeeders(SeederManager $seederManager)
    {
        $seederManager->addSeeder($this->getInstance(ConfigsSeeder::class));
        $seederManager->addSeeder($this->getInstance(PaymentGatewaysSeeder::class));
    }
}
