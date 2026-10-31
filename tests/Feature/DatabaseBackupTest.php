<?php

namespace Tests\Feature;

use App\Services\DatabaseBackup;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    public function test_split_sql_respects_semicolons_inside_string_literals(): void
    {
        $sql = "SET NAMES utf8mb4;\n"
            ."INSERT INTO `items` VALUES ('a;b', 'it\\'s; ok'), (\"x;y\", 'z');\n"
            .'SELECT 1;';

        $statements = array_values(array_filter(
            array_map('trim', DatabaseBackup::splitSql($sql)),
            fn (string $statement) => $statement !== '',
        ));

        $this->assertCount(3, $statements);
        $this->assertSame('SET NAMES utf8mb4', $statements[0]);
        $this->assertStringContainsString("'a;b'", $statements[1]);
        $this->assertStringContainsString("it\\'s; ok", $statements[1]);
        $this->assertSame('SELECT 1', $statements[2]);
    }

    public function test_split_sql_skips_line_and_block_comments(): void
    {
        $sql = "-- komentar; dengan titik koma\n"
            ."# komentar lain; juga\n"
            ."/*!40101 SET NAMES utf8mb4 */;\n"
            ."CREATE TABLE `a` (`b` int);\n";

        $statements = array_values(array_filter(
            array_map('trim', DatabaseBackup::splitSql($sql)),
            fn (string $statement) => $statement !== '',
        ));

        $this->assertCount(1, $statements);
        $this->assertStringStartsWith('CREATE TABLE', $statements[0]);
    }

    public function test_split_sql_handles_backticks_with_semicolons(): void
    {
        $statements = array_values(array_filter(
            array_map('trim', DatabaseBackup::splitSql('CREATE TABLE `weird;name` (`id` int);')),
            fn (string $statement) => $statement !== '',
        ));

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('`weird;name`', $statements[0]);
    }

    public function test_mysql_and_sqlite_drivers_are_detected(): void
    {
        $this->assertTrue(DatabaseBackup::isSqlite());
        $this->assertFalse(DatabaseBackup::isMysql());
    }
}
