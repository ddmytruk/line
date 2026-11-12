<?php

declare(strict_types=1);

namespace Payroll\Application\History;

enum HistoryEntryKind: string
{
    case Calculated = 'calculated';
    case Recalculated = 'recalculated';
    case RecalculationIgnored = 'recalculation ignored';
    case Adjusted = 'adjusted';
}
