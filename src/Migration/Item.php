<?php

declare(strict_types=1);

/**
 * @copyright   Copyright 2020, CitrusGeneration. All Rights Reserved.
 * @author      take64 <take64@citrus.tk>
 * @license     http://www.citrus.tk/
 */

namespace Citrus\Migration;

/**
 * マイグレーション用アイテム
 */
abstract class Item
{
    /** @var string object name */
    protected $object_name = '';



    /**
     * migration.sh up
     *
     * @return string SQL
     */
    abstract public function up(): string;



    /**
     * migration.sh down
     *
     * @return string SQL
     */
    abstract public function down(): string;



    /**
     * バージョンの取得
     *
     * @return string バージョン取得
     */
    public function version(): string
    {
        // '_' で分割した２番目の要素がバージョン
        $class_names = explode('_', static::class);
        return $class_names[1];
    }
}
