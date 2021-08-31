<?php

namespace Crm\GoPayModule\DI;

use Kdyby\Translation\DI\ITranslationProvider;
use Nette\DI\CompilerExtension;

final class GoPayModuleExtension extends CompilerExtension implements ITranslationProvider
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
    public function getTranslationResources()
    {
        return [__DIR__ . '/../lang/'];
    }
}
