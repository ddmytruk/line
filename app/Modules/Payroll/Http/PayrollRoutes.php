<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Http;

use Illuminate\Contracts\Routing\Registrar;

/** The HTTP API of Payroll (ADR 0014). */
final class PayrollRoutes
{
    public static function register(Registrar $router): void
    {
        $router->post('lines/{lineId}/calculations', RecordValueController::class);
        $router->post('lines/{lineId}/adjustments', AdjustController::class);
        $router->get('lines/{lineId}/history', HistoryController::class);
    }
}
