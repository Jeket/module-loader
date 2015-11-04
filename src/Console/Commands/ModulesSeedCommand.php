<?php

namespace KodiCMS\ModulesLoader\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use KodiCMS\ModulesLoader\ModulesInstaller;
use Symfony\Component\Console\Input\InputOption;
use KodiCMS\ModulesLoader\ModulesLoaderFacade as ModulesLoader;

class ModulesSeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     */
    protected $name = 'modules:seed';

    /**
     * Execute the console command.
     */
    public function fire()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->output->writeln('<info>Seeding KodiCMS modules...</info>');
        $installer = new ModulesInstaller(ModulesLoader::getRegisteredModules());

        $installer->cleanOutputMessages();
        $installer->seedModules();

        foreach ($installer->getOutputMessages() as $message) {
            $this->output->writeln($message);
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}
