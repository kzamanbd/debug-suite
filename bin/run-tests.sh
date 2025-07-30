#!/bin/bash

# Simple test runner script for Debug Suite
echo "Setting up WordPress test environment..."

# Set environment variables
export WP_TESTS_DIR="/tmp/wordpress-tests-lib"
export WP_CORE_DIR="/tmp/wordpress/"

# Check if test environment exists
if [ ! -f "$WP_TESTS_DIR/includes/functions.php" ]; then
    echo "WordPress test environment not found. Installing..."
    ./bin/install-wp-tests.sh debug_suite_test root '' localhost latest
fi

echo "Running unit tests..."
vendor/bin/phpunit --testsuite=unit --verbose

echo "Running integration tests..."
vendor/bin/phpunit --testsuite=integration --verbose

echo "Test run completed."
