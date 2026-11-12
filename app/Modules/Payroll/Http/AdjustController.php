<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Http;

use Common\Application\Command\CommandBus;
use Illuminate\Http\Request;
use Payroll\Application\Command\AddManualAdjustment;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /lines/{lineId}/adjustments: a specialist adjusts the line.
 * Body: commandId, expectedVersion (the version of the history they saw), amount, currency, comment, specialistId.
 */
final readonly class AdjustController
{
    public function __construct(private CommandBus $bus) {}

    public function __invoke(Request $request, string $lineId): Response
    {
        return Responses::of(function () use ($request, $lineId): Response {
            $body = JsonBody::of($request);
            $this->bus->dispatch(new AddManualAdjustment(
                $body->nonEmptyString('commandId'),
                $lineId,
                $body->nonNegativeInt('expectedVersion'),
                $body->string('amount'),
                $body->string('currency'),
                $body->string('comment'),
                $body->string('specialistId'),
            ));

            return new Response(status: Response::HTTP_NO_CONTENT);
        });
    }
}
