<?php

namespace Mnabialek\LaravelSimpleModules;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Database\Seeder;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Mnabialek\LaravelSimpleModules\Traits\Normalizer;
use Mnabialek\LaravelSimpleModules\Traits\Replacer;

class SimpleModule
{
    use Replacer;
    use Normalizer;

    /**
     * @var Application
     */
    protected $app;

    protected $modules = null;

    /**
     * SimpleModule constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Runs main seeders for all active modules
     *
     * @param Seeder $seeder
     */
    public function seed(Seeder $seeder)
    {
        $this->withSeeders()->each(function ($module) use ($seeder) {
            $seeder->call($module->getSeederClass());
        });
    }

    /**
     * Load routes for active modules
     *
     * @param Registrar $router
     */
    public function loadRoutes(Registrar $router)
    {
        $this->withRoutes()->each(function ($module) use ($router) {
            $router->group(['namespace' => $module->getRouteControllerNamespace()],
                function ($router) use ($module) {
                    require($this->app->basePath() . DIRECTORY_SEPARATOR .
                        $module->getRoutesFilePath());
                });
        });
    }

    /**
     * Load factories for active modules
     */
    public function loadFactories($factory)
    {
        $this->withFactories()->each(function ($module) {
            require($module->getModuleFactoryFilePath());
        });
    }

    /**
     * Load service providers for active modules
     */
    public function loadServiceProviders()
    {
        $this->withServiceProviders()->each(function ($module) {
            $this->app->register($module->getServiceProviderClass());
        });
    }

    /**
     * Get all routable modules (active and having routes file)
     *
     * @return array
     */
    public function withRoutes()
    {
        return $this->filterActiveByMethod('hasRoutes');
    }

    /**
     * Get all routable modules (active and having routes file)
     *
     * @return array
     */
    public function withFactories()
    {
        return $this->filterActiveByMethod('hasFactory');
    }

    /**
     * Get all modules that have service providers (active and having service
     * provider file)
     *
     * @return array
     */
    public function withServiceProviders()
    {
        return $this->filterActiveByMethod('hasServiceProvider');
    }

    /**
     * Get all modules that have seeders (active and having seeder file)
     *
     * @return array
     */
    public function withSeeders()
    {
        return $this->filterActiveByMethod('hasSeeder');
    }

    protected function filterActiveByMethod($methodName)
    {
        return $this->modules()->filter(function ($module) use ($methodName) {
            return $module->isActive() && $module->$methodName();
        });
    }

    /**
     * Get all modules from configuration file
     *
     * @return array
     */
    protected function modules()
    {
        if ($this->modules === null) {
            $this->loadModules();
        }
        
        return $this->modules();
    }

    protected function loadModules()
    {
    }

    /**
     * Get active modules
     *
     * @return Collection
     */
    public function active(Collection $modules)
    {
        return $this->filter($modules, 'isActive');
    }

    /**
     * Get all modules
     *
     * @return array
     */
    public function all()
    {
        return $this->modules();
    }

    /**
     * Get disabled modules
     *
     * @return array
     */
    public function disabled()
    {
        return $this->all()->reject(function ($module) {
            $module->isActive();
        });
    }



    /**
     * Verifies whether given module exists
     *
     * @param string $module
     *
     * @return bool
     */
    public function exists($module)
    {
        return in_array($this->getModuleName($module), $this->all());
    }
}
