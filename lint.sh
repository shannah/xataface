#!/bin/bash

# Xataface PHP 8.5 Compatibility Linter
# This script checks PHP code for PHP 8.5 compatibility issues

# Don't exit on phpcs errors (we want to see the results)
set +e

echo "======================================"
echo "Xataface PHP 8.5 Compatibility Linter"
echo "======================================"
echo ""

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "Error: vendor directory not found. Please run 'composer install' first."
    exit 1
fi

# Check if PHPCompatibility is installed
if [ ! -d "vendor/phpcompatibility/php-compatibility" ]; then
    echo "Error: PHPCompatibility not found. Please run 'composer install' first."
    exit 1
fi

# PHP options: increase memory limit and disable xdebug
PHP_OPTIONS="-d memory_limit=512M -d xdebug.mode=off"

# Configure PHP_CodeSniffer with PHPCompatibility
if ! php $PHP_OPTIONS vendor/bin/phpcs --config-show 2>/dev/null | grep -q "installed_paths.*phpcompatibility"; then
    echo "Configuring PHP_CodeSniffer with PHPCompatibility..."
    php $PHP_OPTIONS vendor/bin/phpcs --config-set installed_paths vendor/phpcompatibility/php-compatibility
    echo ""
fi

# Run the linter
echo "Running PHP 8.5 compatibility check..."
echo ""

# Allow specific files/directories to be passed as arguments
if [ $# -gt 0 ]; then
    php $PHP_OPTIONS vendor/bin/phpcs --standard=phpcs.xml "$@"
    EXIT_CODE=$?
else
    php $PHP_OPTIONS vendor/bin/phpcs --standard=phpcs.xml
    EXIT_CODE=$?
fi

echo ""
echo "======================================"
if [ $EXIT_CODE -eq 0 ]; then
    echo "Linting complete! No compatibility issues found."
else
    echo "Linting complete! Found compatibility issues (exit code: $EXIT_CODE)"
fi
echo "======================================"

exit $EXIT_CODE
