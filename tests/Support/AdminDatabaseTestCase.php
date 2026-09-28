<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

abstract class AdminDatabaseTestCase extends TestCase
{
    private static ?string $testDatabase = null;

    private static ?PDO $server = null;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('FANHUB_MYSQL_TESTS') !== '1') {
            $this->markTestSkipped('Set FANHUB_MYSQL_TESTS=1 to run isolated MySQL integration tests.');
        }
        config(['database.default' => 'mysql', 'session.driver' => 'array', 'cache.default' => 'array', 'hashing.bcrypt.rounds' => 4]);
        if (self::$testDatabase === null) {
            $connection = config('database.connections.mysql');
            self::$server = new PDO(
                "mysql:host={$connection['host']};port={$connection['port']};charset=utf8mb4",
                $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
            self::$testDatabase = 'fanhub_admin_test_'.bin2hex(random_bytes(6));
            self::$server->exec('CREATE DATABASE '.self::$testDatabase.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            config(['database.connections.mysql.database' => self::$testDatabase]);
            DB::purge('mysql');
            if (Artisan::call('migrate', ['--force' => true]) !== 0 || Artisan::call('db:seed', ['--force' => true]) !== 0) {
                throw new \RuntimeException(Artisan::output());
            }
        } else {
            config(['database.connections.mysql.database' => self::$testDatabase]);
            DB::purge('mysql');
        }
        DB::beginTransaction();
        $this->admin = User::where('email', 'admin@fanhubplus.com')->firstOrFail();
        $this->admin->password_hash = 'AdminTest12345!';
        $this->admin->save();
    }

    protected function tearDown(): void
    {
        if (self::$testDatabase !== null && $this->app) {
            while (DB::connection('mysql')->transactionLevel() > 0) {
                DB::connection('mysql')->rollBack();
            }
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server && self::$testDatabase && preg_match('/^fanhub_admin_test_[a-f0-9]{12}$/', self::$testDatabase)) {
            self::$server->exec('DROP DATABASE '.self::$testDatabase);
        }
        self::$testDatabase = null;
        self::$server = null;
        parent::tearDownAfterClass();
    }
}
