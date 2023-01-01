<?php

declare(strict_types=1);

/**
 * @copyright   Copyright 2020, CitrusGeneration. All Rights Reserved.
 * @author      take64 <take64@citrus.tk>
 * @license     http://www.citrus.tk/
 */

namespace Citrus\Migration;

use Citrus\Console\ConsoleOutput;
use Citrus\Database\DSN;
use Citrus\Variable\Dates;
use Citrus\Variable\Measures;
use PDO;
use PDOStatement;

/**
 * マイグレーションバージョン管理
 */
class VersionManager
{
    use ConsoleOutput;

    /** @var PDO DBハンドラ */
    private $handler;

    /** @var DSN DB接続情報 */
    private $dsn;



    /**
     * constructor.
     *
     * @param DSN $dsn
     */
    public function __construct(DSN $dsn)
    {
        $this->dsn = $dsn;
        $this->handler = new PDO($this->dsn->toStringWithAuth());
        // マイグレーションのセットアップ
        $this->setupMigration();
    }

    /**
     * マイグレーションのセットアップ
     */
    public function setupMigration(): void
    {
        // マイグレーション管理テーブルの生成
        $query = <<<'SQL'
CREATE TABLE IF NOT EXISTS {SCHEMA}cf_migrations (
    version_code CHARACTER VARYING(32) NOT NULL,
    migrated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version_code)
);
SQL;
        self::executeQuery($query);
    }

    /**
     * マイグレーションの正方向実行
     *
     * @param Item $item
     */
    public function up(Item $item): void
    {
        // バージョン
        $version = $item->version();
        // クラス名
        $class_name = get_class($item);
        // ログ：バージョン操作開始
        $this->format('%s up. executing.', $class_name);
        // バージョンアップできるか
        if (true === $this->existVersion($version))
        {
            // ログ：バージョン操作対象外
            $this->format('%s up. is already.', $class_name);
            return;
        }

        // 正方向クエリを実行して時間計測する
        $result = null;
        $execute_microsecond = Measures::time(function () use ($item, &$result): void {
            $query = $item->up();
            $result = $this->executeQuery($query);
        });

        // バージョン情報の登録
        if (true === $result)
        {
            $this->createVersion($version);
        }

        // ログ：実行結果
        $method = (true === $result ? 'success' : 'failure');
        $this->$method(sprintf('%s up. %s. %f μs.', $class_name, $method, $execute_microsecond));
    }

    /**
     * マイグレーションの逆方向実行
     *
     * @param Item $item
     */
    public function down(Item $item): void
    {
        // バージョン
        $version = $item->version();
        // クラス名
        $class_name = get_class($item);
        // ログ：バージョン操作開始
        $this->format('%s down. executing.', $class_name);
        // バージョンダウンできるか
        if (false === $this->existVersion($version))
        {
            // ログ：バージョン操作対象外
            $this->format('%s down. not found.', $class_name);
            return;
        }

        // 逆方向クエリを実行して時間計測する
        $result = null;
        $execute_microsecond = Measures::time(function () use ($item, &$result): void {
            $query = $item->down();
            $result = $this->executeQuery($query);
        });

        // バージョン情報の削除
        if (true === $result)
        {
            $this->deleteVersion($version);
        }

        // ログ：実行結果
        $method = (true === $result ? 'success' : 'failure');
        $this->$method(sprintf('%s down. %s. %f μs.', $class_name, $method, $execute_microsecond));
    }

    /**
     * スキーマ指定の置換
     *
     * @param string $query 置換対象文字列
     * @return string 置換済み文字列
     */
    public function replaceSchema(string $query): string
    {
        // スキーマに文字列があれば、ドットでつなぐ
        $schema = $this->dsn->schema;
        if (false === is_null($schema) && 0 < strlen($schema))
        {
            $schema .= '.';
        }
        return str_replace('{SCHEMA}', ($schema ?? ''), $query);
    }

    /**
     * クエリの実行
     *
     * @param string $query 実行したいクエリー
     * @return bool true:成功,false:失敗
     */
    private function executeQuery(string $query): bool
    {
        // スキーマ置換
        $query = $this->replaceSchema($query);
        // クエリ実行
        $result = $this->handler->exec($query);

        return (false === $result ? false : true);
    }

    /**
     * プリペアクエリの実行
     *
     * @param string $query      実行したいクエリー
     * @param array  $parameters パラメータ
     * @return PDOStatement|null
     */
    private function prepareQuery(string $query, array $parameters): ?PDOStatement
    {
        // スキーマ置換
        $query = $this->replaceSchema($query);
        // プリペア実行
        $statement = $this->handler->prepare($query);
        $result = $statement->execute($parameters);
        return (true === $result ? $statement : null);
    }

    /**
     * 指定のバージョンの実行ログが存在するか
     *
     * @param string $version チェックしたいバージョン
     * @return bool true:存在する,false:存在しない
     */
    private function existVersion(string $version): bool
    {
        $statement = $this->prepareQuery(
            'SELECT * FROM {SCHEMA}cf_migrations WHERE version_code = :version_code;',
            [
                ':version_code' => $version,
            ],
        );
        if (true === is_null($statement))
        {
            return false;
        }
        // 件数が0を超える場合、対象バージョンが存在する
        return (0 < count($statement->fetchAll(PDO::FETCH_ASSOC)));
    }

    /**
     * バージョン情報の登録
     *
     * @param string $version
     */
    private function createVersion(string $version): void
    {
        $now = Dates::now()->format('Y-m-d H:i:s T');
        $this->prepareQuery(
            'INSERT INTO {SCHEMA}cf_migrations (version_code, migrated_at) VALUES (:version_code, :migrated_at);',
            [
                ':version_code' => $version,
                ':migrated_at'  => $now,
            ],
        );
    }

    /**
     * バージョン情報の削除
     *
     * @param string $version
     */
    private function deleteVersion(string $version): void
    {
        $this->prepareQuery(
            'DELETE FROM {SCHEMA}cf_migrations WHERE version_code = :version_code;',
            [
                ':version_code' => $version,
            ],
        );
    }
}
