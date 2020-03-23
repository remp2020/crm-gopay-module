<?php

namespace Crm\GoPayModule\Seeders;

use Crm\ApplicationModule\Builder\ConfigBuilder;
use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\ApplicationModule\Config\Repository\ConfigCategoriesRepository;
use Crm\ApplicationModule\Config\Repository\ConfigsRepository;
use Crm\ApplicationModule\Seeders\ConfigsTrait;
use Crm\ApplicationModule\Seeders\ISeeder;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigsSeeder implements ISeeder
{
    use ConfigsTrait;

    private $configCategoriesRepository;

    private $configsRepository;

    private $configBuilder;

    public function __construct(
        ConfigCategoriesRepository $configCategoriesRepository,
        ConfigsRepository $configsRepository,
        ConfigBuilder $configBuilder
    ) {
        $this->configCategoriesRepository = $configCategoriesRepository;
        $this->configsRepository = $configsRepository;
        $this->configBuilder = $configBuilder;
    }

    public function seed(OutputInterface $output)
    {
        $category = $this->configCategoriesRepository->findBy('name', 'payments.config.category');
        $sorting = 2100;

        $this->addConfig(
            $output,
            $category,
            'gopay_go_id',
            ApplicationConfig::TYPE_STRING,
            'gopay.config.gopay_go_id.name',
            'gopay.config.gopay_go_id.description',
            null,
            $sorting++
        );

        $this->addConfig(
            $output,
            $category,
            'gopay_client_id',
            ApplicationConfig::TYPE_STRING,
            'gopay.config.gopay_client_id.name',
            'gopay.config.gopay_client_id.description',
            null,
            $sorting++
        );

        $this->addConfig(
            $output,
            $category,
            'gopay_client_secret',
            ApplicationConfig::TYPE_STRING,
            'gopay.config.gopay_client_secret.name',
            'gopay.config.gopay_client_secret.description',
            null,
            $sorting++
        );

        $this->addConfig(
            $output,
            $category,
            'gopay_eet_enabled',
            ApplicationConfig::TYPE_BOOLEAN,
            'gopay.config.gopay_eet_enabled.name',
            'gopay.config.gopay_eet_enabled.description',
            0,
            $sorting++
        );

        $this->addConfig(
            $output,
            $category,
            'gopay_test_mode',
            ApplicationConfig::TYPE_BOOLEAN,
            'gopay.config.gopay_test_mode.name',
            'gopay.config.gopay_test_mode.description',
            1,
            $sorting++
        );
    }
}
