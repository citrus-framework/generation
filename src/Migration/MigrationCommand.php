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
    /** @var string[] command options */
    protected array $options = [
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
            case ActionType::GENERATE->value:
                // 生成処理
                $migration->generate($name);
                break;
            case ActionType::MIGRATION_UP->value:
                // マイグレーションUP実行
                $migration->up($version);
                break;
            case ActionType::MIGRATION_DOWN->value:
                // マイグレーションDOWN実行
                $migration->down($version);
                break;
            case ActionType::MIGRATION_REBIRTH->value:
                // マイグレーションREBIRTH実行
                $migration->rebirth($version);
                break;
            default:
        }
    }
}
