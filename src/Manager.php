<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026-present The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version >8.5
 * @package Module
 */

namespace FireHub\Module;

use FireHub\Core\Boundary\Module;
use FireHub\Core\Boundary\Module\ {
    Dependencies, RegistersServices
};
use FireHub\Core\Boundary\Container\Registrar;
use FireHub\Module\Exception\ {
    CircularModuleDependencyException, ModuleAlreadyRegisteredException, ModuleDependencyNotFoundException,
    ModuleManagerFrozenException, ModuleNotFoundException
};

/**
 * ### Manages registered application modules
 *
 * Maintains an ordered registry of modules without controlling application execution or introducing an independent
 * module lifecycle.
 * @since 1.0.0
 */
final class Manager {

    /**
     * ### Registered modules
     * @var array<class-string<\FireHub\Core\Boundary\Module>, \FireHub\Core\Boundary\Module>
     */
    private array $modules = [];

    /**
     * ### Module registry freeze state
     * @since 1.0.0
     */
    private bool $frozen = false;

    /**
     * ### Registers a module
     * @since 1.0.0
     *
     * @param \FireHub\Core\Boundary\Module $module <p>
     * Module to register.
     * </p>
     *
     * @throws \FireHub\Module\Exception\ModuleAlreadyRegisteredException If the module is already registered.
     * @throws \FireHub\Module\Exception\ModuleManagerFrozenException If the manager is frozen.
     *
     * @return self The manager instance.
     */
    public function register (Module $module):self {

        if ($this->frozen)
            throw new ModuleManagerFrozenException;

        $class = $module::class;

        if (isset($this->modules[$class]))
            throw new ModuleAlreadyRegisteredException;

        $this->modules[$class] = $module;

        return $this;


    }

    /**
     * ### Checks whether a module is registered
     * @since 1.0.0
     *
     * @param class-string<\FireHub\Core\Boundary\Module> $class <p>
     * Module class name.
     * </p>
     *
     * @return bool True if the module is registered.
     */
    public function has (string $class):bool {

        return isset($this->modules[$class]);

    }

    /**
     * ### Gets a registered module
     * @since 1.0.0
     *
     * @param class-string<\FireHub\Core\Boundary\Module> $class <p>
     * Module class name.
     * </p>
     *
     * @throws \FireHub\Module\Exception\ModuleNotFoundException If the module is not registered.
     *
     * @return \FireHub\Core\Boundary\Module Registered module.
     */
    public function get (string $class):Module {

        return $this->modules[$class]
            ?? throw new ModuleNotFoundException;

    }

    /**
     * ### Gets all registered modules
     * @since 1.0.0
     *
     * @return array<class-string<\FireHub\Core\Boundary\Module>, \FireHub\Core\Boundary\Module> Registered modules.
     */
    public function all ():array {

        return $this->modules;

    }

    /**
     * ### Gets modules in dependency order
     *
     * Resolves module dependencies using topological sorting.
     *
     * Dependencies are placed before the modules that require them. Modules without dependency relationships
     * preserve their registration order.
     * @since 1.0.0
     *
     * @uses \FireHub\Module\Manager::visit() Visits a module dependency graph.
     *
     * @throws \FireHub\Module\Exception\ModuleDependencyNotFoundException If a required module is not registered.
     * @throws \FireHub\Module\Exception\CircularModuleDependencyException If circular dependencies are detected.
     *
     * @return list<\FireHub\Core\Boundary\Module> Modules in dependency order.
     */
    public function ordered ():array {

        /** @var array<class-string<\FireHub\Core\Boundary\Module>, int> $states */
        $states = [];

        /** @var list<\FireHub\Core\Boundary\Module> $ordered */
        $ordered = [];

        foreach ($this->modules as $module)
            $this->visit($module, $states, $ordered);

        return $ordered;

    }

    /**
     * ### Registers services provided by modules
     *
     * Invokes service registration for all registered modules in dependency order.
     *
     * Modules that do not implement the registration capability are skipped.
     *
     * Service registration must occur before the container is frozen.
     * @since 1.0.0
     *
     * @uses \FireHub\Module\Manager::ordered() Gets modules in dependency order.
     * @uses \FireHub\Core\Boundary\Module\RegistersServices::register() To register services.
     *
     * @param \FireHub\Core\Boundary\Container\Registrar $registrar <p>
     * Service registrar.
     * </p>
     *
     * @throws \FireHub\Module\Exception\ModuleDependencyNotFoundException If a required module is not registered.
     * @throws \FireHub\Module\Exception\CircularModuleDependencyException If circular dependencies are detected.
     *
     * @return void
     */
    public function registerServices (Registrar $registrar):void {

        foreach ($this->ordered() as $module) {

            if ($module instanceof RegistersServices)
                $module->register($registrar);

        }

    }

    /**
     * ### Freezes the module registry
     *
     * Prevents further module registration while preserving access to previously registered modules.
     * @since 1.0.0
     *
     * @return void
     */
    public function freeze ():void {

        $this->frozen = true;

    }

    /**
     * ### Visits a module dependency graph
     *
     * Performs a depth-first traversal to resolve module ordering and detect circular dependencies.
     * @since 1.0.0
     *
     * @uses \FireHub\Core\Boundary\Module\Dependencies::dependencies() Gets the module dependencies.
     *
     * @param \FireHub\Core\Boundary\Module $module <p>
     * Module to visit.
     * </p>
     * @param array<class-string<Module>, int> $states <p>
     * Module visitation states.
     * </p>
     * @param list<\FireHub\Core\Boundary\Module> $ordered <p>
     * Resolved module order.
     * </p>
     *
     * @throws \FireHub\Module\Exception\ModuleDependencyNotFoundException If a required module is not registered.
     * @throws \FireHub\Module\Exception\CircularModuleDependencyException If circular dependencies are detected.
     *
     * @return void
     */
    private function visit (Module $module, array &$states, array &$ordered):void {

        $class = $module::class;

        $state = $states[$class] ?? 0;

        if ($state === 2) return;

        if ($state === 1)
            throw new CircularModuleDependencyException(
                context: [
                    'module' => $class,
                ]
            );

        $states[$class] = 1;

        if ($module instanceof Dependencies) {

            foreach ($module->dependencies() as $dependency) {

                if (!isset($this->modules[$dependency]))
                    throw new ModuleDependencyNotFoundException(
                        context: [
                            'dependency' => $dependency,
                        ]
                    );

                $this->visit($this->modules[$dependency], $states, $ordered);

            }

        }

        $states[$class] = 2;

        $ordered[] = $module;

    }

}