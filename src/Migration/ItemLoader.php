<?php

declare(strict_types=1);

/**
 * @copyright   Copyright 2026, CitrusGeneration. All Rights Reserved.
 * @author      take64 <take64@citrus.tk>
 * @license     http://www.citrus.tk/
 */

namespace Citrus\Migration;

/**
 * マイグレーションファイルの読み込み
 */
class ItemLoader
{
    /**
     * constructor.
     * @param string $output_dir マイグレーションファイル格納ディレクトリ
     */
    public function __construct(private string $output_dir)
    {
    }

    /**
     * 対象のマイグレーションファイル名の取得
     * @param string|null $version バージョン指定(指定がなければ全部)
     * @return string[]
     */
    public function filenames(string|null $version = null): array
    {
        $filenames = [];

        // 対象ファイルの取得
        /** @var string $filename ex. Citrus_XXXXXXXXXXXXXX_CreateTableXXXXXs.php */
        foreach (scandir($this->output_dir) as $filename)
        {
            // ファイルでなければスルー
            if (is_file(sprintf('%s/%s', $this->output_dir, $filename)) === false)
            {
                continue;
            }

            // バージョン指定時に、対象バージョン以外だったらスルー
            if ($version !== null && strpos($filename, $version) === false)
            {
                continue;
            }

            $filenames[] = $filename;
        }

        return $filenames;
    }

    /**
     * マイグレーションクラスのインスタンス取得
     * @param string $filename ファイル名
     * @return Item
     */
    public function instance(string $filename): Item
    {
        // マイグレーションクラス名
        $class_name = str_replace('.php', '', $filename);

        // ファイルを読み込み
        include_once(sprintf('%s/%s', $this->output_dir, $filename));
        return new $class_name();
    }
}
