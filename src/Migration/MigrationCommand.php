<?php

declare(strict_types=1);

/**
 * @copyright   Copyright 2020, CitrusGeneration. All Rights Reserved.
 * @author      take64 <take64@citrus.tk>
 * @license     http://www.citrus.tk/
 */

namespace Citrus\Migration;

use Citrus\CitrusException;
use Citrus\Console;
use Citrus\Migration;

/**
 * マイグレーションコマンド
 */
class MigrationCommand extends Console
{
    /** @var array command options */
    protected $options = [
        'action::',
        'name:',
        'version:',
    ];



    /**
     * {@inheritDoc}
     *
     * @throws CitrusException
     */
    public function execute(): void
    {
        parent::execute();

        $action = $this->parameter('action');
        $name = $this->parameter('name');
        $version = $this->parameter('version');

        $migration = Migration::sharedInstance()->loadConfigures($this->configures);

        switch ($action)
        {
            // 生成処理
            case Migration::ACTION_GENERATE:
                $migration->generate($name);
                break;
            // マイグレーションUP実行
            case Migration::ACTION_MIGRATION_UP:
                $migration->up($version);
                break;
            // マイグレーションDOWN実行
            case Migration::ACTION_MIGRATION_DOWN:
                $migration->down($version);
                break;
            // マイグレーションREBIRTH実行
            case Migration::ACTION_MIGRATION_REBIRTH:
                $migration->rebirth($version);
                break;
            default:
        }
    }
}
