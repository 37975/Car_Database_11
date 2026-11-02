<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use PDO;
use Throwable;

/**
 * Read-only database viewer for the admin area.
 * Shows every business table: structure (columns, PK, FK, indexes, DDL) and its rows,
 * plus a SQL console that can only read (read-only connection / read-only transaction).
 * Works on SQLite (deployed) and MySQL (local Docker).
 */
class DatabaseController extends Controller
{
    /** Tables that may be viewed (whitelist: table names are never taken from user input as-is). */
    private const TABLES = [
        'MEMBERSHIP_TIER' => 'Loyalty tiers',
        'MEMBER' => 'Members and admins',
        'BRAND' => 'Car brands',
        'CATEGORY' => 'Car categories',
        'CAR' => 'Cars for sale',
        'CART' => 'One cart per member',
        'CART_ITEM' => 'Cars in a cart (M:N)',
        'ORDERS' => 'Placed orders',
        'ORDER_ITEM' => 'Cars in an order (M:N)',
        'REVIEW' => 'Reviews and ratings',
        'ADMIN_LOG' => 'Audit log of admin actions',
        'migrations' => 'Laravel migration history',
    ];

    /** Columns whose values are never shown. */
    private const HIDDEN = [
        'MEMBER' => ['password', 'remember_token'],
    ];

    private const MAX_ROWS = 500;

    /** Example read-only queries that run on SQLite and MySQL alike. */
    private const EXAMPLES = [
        'Orders with member and totals' => "SELECT o.order_id, m.first_name, m.last_name, o.status,\n       o.subtotal, o.discount_amount, o.total_amount, o.points_earned\nFROM ORDERS o\nJOIN MEMBER m ON m.member_id = o.member_id\nORDER BY o.order_id DESC",
        'Order lines vs current price' => "SELECT oi.order_id, c.model_name, oi.quantity,\n       oi.unit_price, c.price AS current_price, c.stock_qty\nFROM ORDER_ITEM oi\nJOIN CAR c ON c.car_id = oi.car_id\nORDER BY oi.order_id DESC",
        'Members, points and tier' => "SELECT m.member_id, m.email, m.role, m.points,\n       t.tier_name, t.min_points, t.discount_percent\nFROM MEMBER m\nJOIN MEMBERSHIP_TIER t ON t.tier_id = m.tier_id\nORDER BY m.points DESC",
        'Average rating per car' => "SELECT c.car_id, c.model_name,\n       COUNT(r.review_id) AS reviews,\n       ROUND(AVG(r.rating), 2) AS avg_rating\nFROM CAR c\nLEFT JOIN REVIEW r ON r.car_id = c.car_id\nGROUP BY c.car_id, c.model_name\nORDER BY avg_rating DESC, reviews DESC",
        'Cars per brand and category' => "SELECT b.brand_name, cat.category_name,\n       COUNT(*) AS cars, SUM(c.stock_qty) AS units_in_stock\nFROM CAR c\nJOIN BRAND b ON b.brand_id = c.brand_id\nJOIN CATEGORY cat ON cat.category_id = c.category_id\nGROUP BY b.brand_name, cat.category_name\nORDER BY b.brand_name, cat.category_name",
        'Latest admin actions' => "SELECT l.log_id, m.email AS admin, l.action, l.subject_type,\n       l.subject_id, l.description, l.changes, l.created_at\nFROM ADMIN_LOG l\nLEFT JOIN MEMBER m ON m.member_id = l.member_id\nORDER BY l.log_id DESC\nLIMIT 10",
    ];

    public function index(): View
    {
        $tables = [];

        foreach (self::TABLES as $name => $description) {
            if (! Schema::hasTable($name)) {
                continue;
            }

            $tables[] = [
                'name' => $name,
                'description' => $description,
                'rows' => DB::table($name)->count(),
                'columns' => count(Schema::getColumns($name)),
                'foreign_keys' => count(Schema::getForeignKeys($name)),
            ];
        }

        return view('admin.database.index', [
            'tables' => $tables,
            'driver' => $this->driverLabel(),
        ]);
    }

    public function show(string $table): View
    {
        abort_unless(array_key_exists($table, self::TABLES) && Schema::hasTable($table), 404);

        $columns = Schema::getColumns($table);
        $indexes = Schema::getIndexes($table);
        $foreignKeys = Schema::getForeignKeys($table);

        $primary = collect($indexes)->firstWhere('primary', true)['columns'] ?? [];
        $unique = collect($indexes)->where('unique', true)->where('primary', false)->pluck('columns')->flatten()->all();
        $fkByColumn = [];
        foreach ($foreignKeys as $fk) {
            foreach ($fk['columns'] as $i => $col) {
                $fkByColumn[$col] = [
                    'table' => $fk['foreign_table'],
                    'column' => $fk['foreign_columns'][$i] ?? null,
                    'on_delete' => $fk['on_delete'] ?? null,
                ];
            }
        }

        $orderBy = $primary[0] ?? $columns[0]['name'];
        $rows = DB::table($table)->orderBy($orderBy)->paginate(25)->withQueryString();

        return view('admin.database.show', [
            'table' => $table,
            'description' => self::TABLES[$table],
            'columns' => $columns,
            'indexes' => $indexes,
            'foreignKeys' => $foreignKeys,
            'primary' => $primary,
            'unique' => $unique,
            'fkByColumn' => $fkByColumn,
            'hidden' => self::HIDDEN[$table] ?? [],
            'rows' => $rows,
            'viewable' => array_keys(self::TABLES),
            'ddl' => $this->ddl($table),
            'rowSql' => 'SELECT * FROM '.$table.' ORDER BY '.$orderBy.' LIMIT 25 OFFSET '.(($rows->currentPage() - 1) * 25),
            'driver' => $this->driverLabel(),
        ]);
    }

    public function query(Request $request): View
    {
        $sql = trim((string) $request->input('sql', ''));
        $result = null;
        $error = null;

        if ($request->isMethod('post')) {
            $request->validate(['sql' => ['required', 'string', 'max:5000']]);

            try {
                $result = $this->runReadOnly($sql);
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.database.query', [
            'sql' => $sql !== '' ? $sql : array_values(self::EXAMPLES)[0],
            'result' => $result,
            'error' => $error,
            'examples' => self::EXAMPLES,
            'driver' => $this->driverLabel(),
        ]);
    }

    /** Run one statement that can only read. Returns ['columns', 'rows', 'count', 'truncated', 'ms']. */
    private function runReadOnly(string $sql): array
    {
        $sql = rtrim($sql, "; \t\n\r");

        if (! preg_match('/^\s*(SELECT|WITH|PRAGMA|EXPLAIN|SHOW|DESCRIBE|DESC)\b/i', $sql)) {
            throw new \RuntimeException('Only read queries are allowed: SELECT, WITH, PRAGMA, EXPLAIN, SHOW or DESCRIBE.');
        }

        if (str_contains($sql, ';')) {
            throw new \RuntimeException('Run one statement at a time (remove the extra ";").');
        }

        $start = microtime(true);
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            // A separate connection opened READ-ONLY: the database file cannot be modified.
            $flags = class_exists(\Pdo\Sqlite::class) ? \Pdo\Sqlite::ATTR_OPEN_FLAGS : PDO::SQLITE_ATTR_OPEN_FLAGS;
            $readOnly = class_exists(\Pdo\Sqlite::class) ? \Pdo\Sqlite::OPEN_READONLY : PDO::SQLITE_OPEN_READONLY;
            $pdo = new PDO('sqlite:'.$connection->getConfig('database'), null, null, [
                $flags => $readOnly,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // MySQL / PostgreSQL: read-only transaction that is always rolled back.
            $pdo = $connection->getPdo();
            $pdo->exec($driver === 'pgsql' ? 'BEGIN READ ONLY' : 'START TRANSACTION READ ONLY');
            try {
                $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            } finally {
                $pdo->exec('ROLLBACK');
            }
        }

        $count = count($rows);

        return [
            'columns' => $count ? array_keys($rows[0]) : [],
            'rows' => array_slice($rows, 0, self::MAX_ROWS),
            'count' => $count,
            'truncated' => $count > self::MAX_ROWS,
            'ms' => round((microtime(true) - $start) * 1000, 1),
        ];
    }

    /** CREATE TABLE statement exactly as stored by the database. */
    private function ddl(string $table): ?string
    {
        try {
            return match (DB::connection()->getDriverName()) {
                'sqlite' => DB::selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table])?->sql,
                'mysql', 'mariadb' => array_values((array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`'))[1] ?? null,
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    private function driverLabel(): string
    {
        $c = DB::connection();

        return match ($c->getDriverName()) {
            'sqlite' => 'SQLite · '.basename((string) $c->getConfig('database')),
            'mysql' => 'MySQL · '.$c->getDatabaseName(),
            'mariadb' => 'MariaDB · '.$c->getDatabaseName(),
            'pgsql' => 'PostgreSQL · '.$c->getDatabaseName(),
            default => $c->getDriverName(),
        };
    }
}
