<?php

declare(strict_types=1);

/**
 * @copyright   Copyright 2020, CitrusGeneration. All Rights Reserved.
 * @author      take64 <take64@citrus.tk>
 * @license     http://www.citrus.tk/
 */

namespace Citrus\Migration;

/**
 * マイグレーション処理タイプ
 */
enum ActionType: string
{
    /** 生成 */
    case GENERATE = 'generate';

    /** UP */
    case UP = 'up';

    /** DOWN */
    case DOWN = 'down';

    /** 再実行 */
    case REBIRTH = 'rebirth';
}
