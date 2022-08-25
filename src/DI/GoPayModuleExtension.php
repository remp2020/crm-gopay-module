<?php

namespace Crm\GoPayModule\DI;

use Contributte\Translation\DI\TranslationProviderInterface;
use Nette\DI\CompilerExtension;

final class GoPayModuleExtension extends CompilerExtension implements TranslationProviderInterface
{
    private $defaults = [
        'recurrenceDateTo' => null,
    ];

    public function loadConfiguration()
    {
        // set default values if user didn't define them
        $this->config = $this->validateConfig($this->defaults);

        // load services from config and register them to Nette\DI Container
        $this->compiler->loadDefinitionsFromConfig(
            $this->loadFromFile(__DIR__.'/../config/config.neon')['services']
        );

        $builder = $this->getContainerBuilder();

        if ($this->config['recurrenceDateTo']) {
            $builder->getDefinition('goPayRecurrent')
                ->addSetup('setRecurrenceDateTo', [$this->config['recurrenceDateTo']]);
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
