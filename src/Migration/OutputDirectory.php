<?php

declare(strict_types=1);

/**
 * @copyright   Copyright 2026, CitrusGeneration. All Rights Reserved.
 * @author      take64 <take64@citrus.tk>
 * @license     http://www.citrus.tk/
 */

namespace Citrus\Migration;

/**
 * マイグレーションファイル格納ディレクトリ
 */
class OutputDirectory
{
    /**
     * constructor.
     * @param string $output_dir 出力ディレクトリ
     */
    public function __construct(private string $output_dir)
    {
    }

    /**
     * 出力ファイル格納ディレクトリパスの設定
     * @param mixed $mode  ディレクトリのパーミッション
     * @param mixed $owner ディレクトリの所有者
     * @param mixed $group ディレクトリのグループ
     */
    public function setup(mixed $mode, mixed $owner, mixed $group): void
    {
        // ディレクトリがなければ生成
        if (file_exists($this->output_dir) === false)
        {
            mkdir($this->output_dir);
            chmod($this->output_dir, $mode);
            chown($this->output_dir, $owner);
            chgrp($this->output_dir, $group);
        }
    }

    /**
     * 生成したマイグレーションファイルの保存
     * @param string $class_name    生成マイグレーションクラス名
     * @param string $file_contents 生成マイグレーションファイル内容
     */
    public function save(string $class_name, string $file_contents): void
    {
        file_put_contents(
            sprintf(
                '%s/%s.php',
                $this->output_dir,
                $class_name
            ),
            $file_contents
        );
    }
}
