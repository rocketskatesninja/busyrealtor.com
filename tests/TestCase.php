<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run against anything that is not a test database.
     *
     * RefreshDatabase begins by dropping every table. phpunit.xml points the suite at
     * `busyrealtor_test`, but those <env> entries are ignored entirely when the config is
     * cached — and `php artisan config:cache` is ordinary practice on a production box. Run
     * the suite there after caching and the connection resolves to the live database, which
     * the first test then wipes.
     *
     * So the guarantee is made structural rather than left to nobody making a mistake. This
     * runs once the config is loaded but before RefreshDatabase gets its hands on the
     * connection, which is the only window where the check is both informed and in time.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        // In-memory SQLite has nothing to lose and no name to check.
        if ($database === ':memory:') {
            return;
        }

        if (! str_ends_with($database, '_test')) {
            throw new RuntimeException(
                "Refusing to run tests against [{$database}] on the [{$connection}] connection. "
                ."The suite drops every table, so it only runs against a database whose name ends in "
                ."'_test'. If this is unexpected, the config is probably cached — clear it with "
                ."`php artisan config:clear`, which is what makes phpunit.xml's settings apply."
            );
        }
    }
}
