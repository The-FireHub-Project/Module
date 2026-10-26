<?php declare(strict_types = 1);

/**
 * This file is part of the FireHub Project ecosystem
 *
 * @author Danijel Galić <danijel.galic@outlook.com>
 * @copyright 2026-present The FireHub Project - All rights reserved
 * @license https://opensource.org/license/Apache-2-0 Apache License, Version 2.0
 *
 * @php-version >=8.1
 * @package Module
 */

namespace FireHub\Module\Exception;

use FireHub\Core\Exception\RuntimeException;

/**
 * ### Required module dependency not found
 *
 * Thrown when a module declares a dependency that has not been registered in the module manager.
 * @since 1.0.0
 */
final class ModuleDependencyNotFoundException extends RuntimeException {

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     */
    protected const string DEFAULT_MESSAGE = 'The module dependency was not found';

}