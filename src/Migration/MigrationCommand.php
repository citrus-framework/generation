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
     * @throws CitrusException
     */
    public function execute(): void
    {
        parent::execute();

        $action = $this->parameter('action');
        $name = $this->parameter('name');
        $version = $this->parameter('version');

        $migration = Migration::sharedInstance()->loadConfigures($this->configures);

        switch (ActionType::from($action))
        {
            // 生成処理
            case ActionType::GENERATE:
                $migration->generate($name);
                break;
                // マイグレーションUP実行
            case ActionType::UP:
                $migration->up($version);
                break;
                // マイグレーションDOWN実行
            case ActionType::DOWN:
                $migration->down($version);
                break;
                // マイグレーションREBIRTH実行
            case ActionType::REBIRTH:
                $migration->rebirth($version);
                break;
            default:
        }
    }
}
