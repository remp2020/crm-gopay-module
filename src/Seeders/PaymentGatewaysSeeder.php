<?php

namespace Crm\GoPayModule\Seeders;

use Crm\ApplicationModule\Seeders\ISeeder;
use Crm\PaymentsModule\Repositories\PaymentGatewaysRepository;
use Symfony\Component\Console\Output\OutputInterface;

class PaymentGatewaysSeeder implements ISeeder
{
    private $paymentGatewaysRepository;
    
    public function __construct(PaymentGatewaysRepository $paymentGatewaysRepository)
    {
        $this->paymentGatewaysRepository = $paymentGatewaysRepository;
    }

    public function seed(OutputInterface $output)
    {
        if (!$this->paymentGatewaysRepository->exists('gopay')) {
            $this->paymentGatewaysRepository->add(
                'GoPay',
                'gopay',
                24,
                true,
                false
            );
            $output->writeln('  <comment>* payment gateway <info>gopay</info> created</comment>');
        } else {
            $output->writeln('  * payment gateway <info>gopay</info> exists');
        }

        if (!$this->paymentGatewaysRepository->exists('gopay_recurrent')) {
            $this->paymentGatewaysRepository->add(
                'GoPay Recurrent',
                'gopay_recurrent',
                25,
                true,
                true
            );
            $output->writeln('  <comment>* payment gateway <info>gopay_recurrent</info> created</comment>');
        } else {
            $output->writeln('  * payment gateway <info>gopay_recurrent</info> exists');
        }
    }
}
