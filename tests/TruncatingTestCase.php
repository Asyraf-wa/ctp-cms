<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTruncation;

/**
 * For tests that need committed rows: InnoDB FULLTEXT indexes only see data after COMMIT,
 * so search tests cannot run inside RefreshDatabase's transaction. Tables filled by
 * migrations (settings, menus) are kept.
 */
abstract class TruncatingTestCase extends TestCase
{
    use DatabaseTruncation;

    /** @var list<string> */
    protected $exceptTables = ['migrations', 'settings', 'menus', 'menu_items'];

    protected function tearDown(): void
    {
        // Leave nothing behind for the transactional suites that run next.
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }
}
