<?php

namespace Crm\GoPayModule\DI;

use Contributte\Translation\DI\TranslationProviderInterface;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class GoPayModuleExtension extends CompilerExtension implements TranslationProviderInterface
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'recurrenceDateTo' => Expect::string()->dynamic(),
        ]);
    }

    public function loadConfiguration()
    {
        // load services from config and register them to Nette\DI Container
        $this->compiler->loadDefinitionsFromConfig(
            $this->loadFromFile(__DIR__.'/../config/config.neon')['services'],
        );

        $builder = $this->getContainerBuilder();

        if ($this->config->recurrenceDateTo) {
            $builder->getDefinition('goPayRecurrent')
                ->addSetup('setRecurrenceDateTo', [$this->config->recurrenceDateTo]);
        }
    }

    /**
     * Return array of directories, that contain resources for translator.
     * @return string[]
     */
    public function getTranslationResources(): array
    {
        return [__DIR__ . '/../lang/'];
    }
}
