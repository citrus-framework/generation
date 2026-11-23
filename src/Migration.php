<?php

declare(strict_types=1);

/**
 * @copyright   Copyright 2020, CitrusGeneration. All Rights Reserved.
 * @author      take64 <take64@citrus.tk>
 * @license     http://www.citrus.tk/
 */

namespace Citrus;

use Citrus\Configure\Configurable;
use Citrus\Database\DSN;
use Citrus\Migration\Item;
use Citrus\Migration\ItemLoader;
use Citrus\Migration\OutputDirectory;
use Citrus\Migration\VersionManager;
use Citrus\Variable\Dates;
use Citrus\Variable\Klass;
use Citrus\Variable\Klass\KlassFileComment;
use Citrus\Variable\Klass\KlassMethod;
use Citrus\Variable\Klass\KlassProperty;
use Citrus\Variable\Klass\KlassReturn;
use Citrus\Variable\Klass\KlassVisibility;
use Citrus\Variable\Singleton;
use Citrus\Variable\Strings;

/**
 * マイグレーション処理
 */
class Migration extends Configurable
{
    use Singleton;

    /** @var VersionManager バージョンマネージャー */
    protected VersionManager $versionManager;

    /** @var string[] ファイル名パターン */
    private array $file_patterns = [
        'CreateTable',
        'DropTable',
        'AlterTable',
        'CreateView',
        'DropView',
        'InsertInto',
    ];

    /**
     * {@inheritDoc}
     */
    public function loadConfigures(#[\SensitiveParameter] array $configures = []): Configurable
    {
        // 設定配列の読み込み
        parent::loadConfigures($configures);

        // 出力ファイル出力パスの設定
        $this->outputDirectory()->setup(
            $this->configures['mode'],
            $this->configures['owner'],
            $this->configures['group']
        );

        // DSN情報
        $dsn = DSN::getInstance()->loadConfigures($this->configures);

        // バージョンマネージャー
        $this->versionManager = new VersionManager($dsn);

        return $this;
    }

    /**
     * マイグレーションファイル生成
     * @param string $generate_name 生成ファイル名
     */
    public function generate(string $generate_name): void
    {
        // 生成時間
        $timestamp = Dates::now()->format('YmdHis');
        // 対象テーブル名
        $object_name = Strings::snakeCase(str_replace($this->file_patterns, '', $generate_name));
        // ファイルコメント
        $file_comment = sprintf('generated Citrus Migration file at %s', Dates::now()->formatTimestamp());
        // クラス名
        $class_name = sprintf('Citrus_%s_%s', $timestamp, $generate_name);
        // メソッド
        $upMethod = (new KlassMethod(KlassVisibility::PUBLIC, 'up', false, 'up'))
            ->setBody(<<<'BODY'
        return <<<SQL
SQL;
BODY)
            ->setReturn((new KlassReturn('string', false, 'SQL')));
        $downMethod = $upMethod->clone()->setName('down')->setComment('down');

        // マイグレーションクラス生成
        $klass = (new Klass($class_name))
            ->setStrictTypes(true)
            ->setFileComment(KlassFileComment::newRaw($file_comment))
            ->setClassComment($class_name)
            ->setExtends('\\' . Item::class)
            ->addProperty(KlassProperty::newProtectedQuotedString('object_name', $object_name, 'object'))
            ->addMethod($upMethod)
            ->addMethod($downMethod);

        // 生成して保存
        $this->outputDirectory()->save($class_name, $klass->toString());
    }

    /**
     * マイグレーションの正方向実行
     * @param string|null $version バージョン指定(指定がなければ全部)
     */
    public function up(?string $version = null): void
    {
        $loader = new ItemLoader($this->configures['output_dir']);

        // バージョンアップ
        foreach ($loader->filenames($version) as $filename)
        {
            $this->versionManager->up($loader->instance($filename));
        }
    }

    /**
     * マイグレーション逆方向実行
     * @param string|null $version バージョン指定(指定がなければ全部)
     */
    public function down(?string $version = null): void
    {
        $loader = new ItemLoader($this->configures['output_dir']);

        // バージョンダウン
        foreach (array_reverse($loader->filenames($version)) as $filename)
        {
            $this->versionManager->down($loader->instance($filename));
        }
    }

    /**
     * マイグレーションREBIRTHの実行
     * @param string|null $version バージョン指定(指定がなければ全部)
     * @throws \Exception
     */
    public function rebirth(?string $version = null): void
    {
        // DOWN
        $this->down($version);
        // UP
        $this->up($version);
    }

    /**
     * {@inheritDoc}
     */
    protected function configureKey(): string
    {
        return 'migration';
    }

    /**
     * {@inheritDoc}
     */
    protected function configureDefaults(): array
    {
        return [
            'mode' => 0755,
            'owner' => posix_getpwuid(posix_geteuid())['name'],
            'group' => posix_getgrgid(posix_getegid())['name'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function configureRequires(): array
    {
        return [
            'database',
            'mode',
            'owner',
            'group',
            'output_dir',
        ];
    }

    /**
     * 出力ファイル格納ディレクトリの取得
     */
    private function outputDirectory(): OutputDirectory
    {
        return new OutputDirectory($this->configures['output_dir']);
    }
}
