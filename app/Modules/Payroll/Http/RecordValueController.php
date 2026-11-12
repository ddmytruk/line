<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Http;

use Common\Application\Command\CommandBus;
use Illuminate\Http\Request;
use Payroll\Application\Command\RecordCalculatedValue;
use Symfony\Component\HttpFoundation\Response;

/** POST /lines/{lineId}/calculations: the payroll calculation sends a value. Body: commandId, amount, currency. */
final readonly class RecordValueController
{
    public function __construct(private CommandBus $bus) {}

    public function __invoke(Request $request, string $lineId): Response
    {
        return Responses::of(function () use ($request, $lineId): Response {
            $body = JsonBody::of($request);
            $this->bus->dispatch(new RecordCalculatedValue($body->nonEmptyString('commandId'), $lineId, $body->string('amount'), $body->string('currency')));

            return new Response(status: Response::HTTP_NO_CONTENT);
        });
    }
}
