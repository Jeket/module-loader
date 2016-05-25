<?php

namespace KodiCMS\ModulesLoader;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Foundation\Application;
use KodiCMS\ModulesLoader\Contracts\ModuleContainerInterface;

class ModulesLoader
{
    use AppNamespaceDetectorTrait;

    /**
     * @var array
     */
    protected $registeredModules = [];

    /**
     * @var array
     */
    protected $shutdownCallbacks = [];

    /**
     * @var string
     */
    protected $defaultContainerClass = '\\App\\DefaultModuleContainer';

    /**
     * @var string
     */
    protected $applicationContainerClass = '\\App\\ModuleContainer';

    /**
     * @param array $modulesList
     */
    public function __construct(array $modulesList)
    {
        register_shutdown_function([$this, 'shutdownHandler']);

        foreach ($modulesList as $moduleName => $modulePath) {
            $moduleNamespace = null;
            $moduleInfo = [];

            if (is_array($modulePath)) {
                $moduleInfo      = $modulePath;
                $moduleNamespace = array_get($modulePath, 'namespace');
                $modulePath      = array_get($modulePath, 'path');
            } else if (is_numeric($moduleName)) {
                $moduleName = $modulePath;
                $modulePath = null;
            }

            if (is_null($modulePath)) {
                $modulePath = base_path('modules'.DIRECTORY_SEPARATOR.$moduleName);
            }

            $this->addModule($moduleName, $modulePath, $moduleNamespace, null, $moduleInfo);
        }

        $this->addModule('App', base_path(), $this->getAppNamespace(), \KodiCMS\ModulesLoader\AppModuleContainer::class);
    }

    /**
     * @return ModuleContainerInterface[]
     */
    public function getRegisteredModules()
    {
        return $this->registeredModules;
    }

    /**
     * @param string $moduleName
     *
     * @return ModuleContainerInterface|null
     */
    public function getRegisteredModule($moduleName)
    {
        return array_get($this->getRegisteredModules(), $moduleName);
    }

    /**
     * @param string      $moduleName
     * @param string|null $modulePath
     * @param string|null $namespace
     * @param string|null $moduleContainerClass
     * @param array       $moduleInfo
     *
     * @return $this
     */
    public function addModule($moduleName, $modulePath = null, $namespace = null, $moduleContainerClass = null, array $moduleInfo = [])
    {
        if (is_null($namespace)) {
            $namespace = 'Modules\\'.$moduleName;
        }

        $namespace = trim($namespace, '\\');

        $customModuleClass = "\\$namespace\\ModuleContainer";

        if ($moduleName == 'App') {
            $customModuleClass = $this->applicationContainerClass;
        }

        if (is_null($moduleContainerClass) or class_exists($customModuleClass)) {
            $moduleContainerClass = $customModuleClass;
        }

        if (!class_exists($moduleContainerClass)) {
            $moduleContainerClass = class_exists($this->defaultContainerClass)
                ? $this->defaultContainerClass
                : \KodiCMS\ModulesLoader\ModuleContainer::class;
        }

        $moduleContainer = new $moduleContainerClass($moduleName, $modulePath, $namespace, $moduleInfo);

        $this->registerModule($moduleContainer);

        return $this;
    }

    /**
     * @param ModuleContainerInterface $module
     */
    public function registerModule(ModuleContainerInterface $module)
    {
        $this->registeredModules[$module->getName()] = $module;
    }

    /**
     * @param Application $app
     *
     * @return $this
     */
    public function registerModules(Application $app)
    {
        foreach ($this->getRegisteredModules() as $module) {
            $module->register($app);
        }

        return $this;
    }

    /**
     * @param Application $app
     *
     * @return $this
     */
    public function bootModules(Application $app)
    {
        foreach ($this->getRegisteredModules() as $module) {
            $module->boot($app);
        }

        return $this;
    }

    /**
     * @param Closure $callback
     */
    public function shutdown(Closure $callback)
    {
        $this->shutdownCallbacks[] = $callback;
    }

    public function shutdownHandler()
    {
        app('events')->fire('app.shutdown');

        foreach ($this->shutdownCallbacks as $callback) {
            $this->call($callback);
        }
    }
}
