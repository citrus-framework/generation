<?php

declare(strict_types=1);

/**
 * @copyright   Copyright 2026, CitrusGeneration. All Rights Reserved.
 * @author      take64 <take64@citrus.tk>
 * @license     http://www.citrus.tk/
 */

namespace Citrus\Migration;

use Citrus\Database\DSN;
use Citrus\Variable\Dates;
use PDO;
use PDOStatement;

/**
 * マイグレーションバージョン管理テーブル
 */
class VersionTable
{
    /**
     * constructor.
     * @param PDO $handler DBハンドラ
     * @param DSN $dsn     DB接続情報
     */
    public function __construct(private PDO $handler, private DSN $dsn)
    {
    }

    /**
     * マイグレーション管理テーブルの生成
     */
    public function setup(): void
    {
        $query = <<<'SQL'
CREATE TABLE IF NOT EXISTS {SCHEMA}cf_migrations (
    version_code CHARACTER VARYING(32) NOT NULL,
    migrated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version_code)
);
SQL;
        $this->execute($query);
    }

    /**
     * クエリの実行
     * @param string $query 実行したいクエリー
     * @return bool true:成功,false:失敗
     */
    public function execute(string $query): bool
    {
        // スキーマ置換
        $query = $this->replaceSchema($query);
        // クエリ実行
        $result = $this->handler->exec($query);

        return $result !== false;
    }

    /**
     * 指定のバージョンの実行ログが存在するか
     * @param string $version チェックしたいバージョン
     * @return bool true:存在する,false:存在しない
     */
    public function exists(string $version): bool
    {
        $statement = $this->prepare(
            'SELECT * FROM {SCHEMA}cf_migrations WHERE version_code = :version_code;',
            [
                ':version_code' => $version,
            ],
        );
        if ($statement === null)
        {
            return false;
        }
        // 件数が0を超える場合、対象バージョンが存在する
        return count($statement->fetchAll(PDO::FETCH_ASSOC)) > 0;
    }

    /**
     * バージョン情報の登録
     * @param string $version 登録するバージョン
     */
    public function create(string $version): void
    {
        $now = Dates::now()->format('Y-m-d H:i:s T');
        $this->prepare(
            'INSERT INTO {SCHEMA}cf_migrations (version_code, migrated_at) VALUES (:version_code, :migrated_at);',
            [
                ':version_code' => $version,
                ':migrated_at' => $now,
            ],
        );
    }

    /**
     * バージョン情報の削除
     * @param string $version 削除するバージョン
     */
    public function delete(string $version): void
    {
        $this->prepare(
            'DELETE FROM {SCHEMA}cf_migrations WHERE version_code = :version_code;',
            [
                ':version_code' => $version,
            ],
        );
    }

    /**
     * スキーマ指定の置換
     * @param string $query 置換対象文字列
     * @return string 置換済み文字列
     */
    public function replaceSchema(string $query): string
    {
        // スキーマに文字列があれば、ドットでつなぐ
        $schema = $this->dsn->schema;
        if ($schema !== null && strlen($schema) > 0)
        {
            $schema .= '.';
        }
        return str_replace('{SCHEMA}', ($schema ?? ''), $query);
    }

    /**
     * プリペアクエリの実行
     * @param string $query      実行したいクエリー
     * @param array  $parameters パラメータ
     * @return PDOStatement|null
     */
    private function prepare(string $query, array $parameters): PDOStatement|null
    {
        // スキーマ置換
        $query = $this->replaceSchema($query);
        // プリペア実行
        $statement = $this->handler->prepare($query);
        $result = $statement->execute($parameters);
        return $result === true ? $statement : null;
    }
}
