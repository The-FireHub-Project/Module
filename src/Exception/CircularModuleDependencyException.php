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
 * ### Circular module dependency exception
 *
 * Thrown when a circular dependency is detected between registered application modules.
 * @since 1.0.0
 */
final class CircularModuleDependencyException extends RuntimeException {

    protected const string DEFAULT_MESSAGE = 'Circular module dependency detected';

}