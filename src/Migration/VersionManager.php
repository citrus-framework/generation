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
use Citrus\Variable\Measures;
use PDO;

/**
 * マイグレーションバージョン管理
 */
class VersionManager
{
    use ConsoleOutput;

    /** @var VersionTable バージョン管理テーブル */
    private VersionTable $versionTable;

    /**
     * constructor.
     * @param DSN $dsn
     */
    public function __construct(DSN $dsn)
    {
        $this->versionTable = new VersionTable(new PDO($dsn->toStringWithAuth()), $dsn);
        // マイグレーションのセットアップ
        $this->setupMigration();
    }

    /**
     * マイグレーションのセットアップ
     */
    public function setupMigration(): void
    {
        $this->versionTable->setup();
    }

    /**
     * マイグレーションの正方向実行
     * @param Item $item
     */
    public function up(Item $item): void
    {
        // バージョン
        $version = $item->version();
        // クラス名
        $class_name = $item::class;
        // ログ：バージョン操作開始
        $this->format('%s up. executing.', $class_name);
        // バージョンアップできるか
        if ($this->versionTable->exists($version) === true)
        {
            // ログ：バージョン操作対象外
            $this->format('%s up. is already.', $class_name);
            return;
        }

        $this->migrate(static fn () => $item->up(), 'up', $class_name, fn () => $this->versionTable->create($version));
    }

    /**
     * マイグレーションの逆方向実行
     * @param Item $item
     */
    public function down(Item $item): void
    {
        // バージョン
        $version = $item->version();
        // クラス名
        $class_name = $item::class;
        // ログ：バージョン操作開始
        $this->format('%s down. executing.', $class_name);
        // バージョンダウンできるか
        if ($this->versionTable->exists($version) === false)
        {
            // ログ：バージョン操作対象外
            $this->format('%s down. not found.', $class_name);
            return;
        }

        $this->migrate(
            static fn () => $item->down(),
            'down',
            $class_name,
            fn () => $this->versionTable->delete($version)
        );
    }

    /**
     * スキーマ指定の置換
     * @param string $query 置換対象文字列
     * @return string 置換済み文字列
     */
    public function replaceSchema(string $query): string
    {
        return $this->versionTable->replaceSchema($query);
    }

    /**
     * クエリを実行して時間計測し、成功したらバージョン情報を更新する
     * @param \Closure $query      実行するクエリーを返す処理
     * @param string   $direction  方向(up or down)
     * @param string   $class_name マイグレーションクラス名
     * @param \Closure $record     成功時のバージョン情報の更新処理
     */
    private function migrate(\Closure $query, string $direction, string $class_name, \Closure $record): void
    {
        // クエリを実行して時間計測する
        $result = false;
        $execute_microsecond = Measures::time(function () use ($query, &$result): void {
            $result = $this->versionTable->execute($query());
        });

        // バージョン情報の更新
        if ($result === true)
        {
            $record();
        }

        // ログ：実行結果
        $method = ($result === true ? 'success' : 'failure');
        $this->$method(sprintf('%s %s. %s. %f μs.', $class_name, $direction, $method, $execute_microsecond));
    }
}
