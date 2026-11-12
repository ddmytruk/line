<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Http;

use Common\Application\Exception\CommandIdReused;
use Common\Application\Exception\ConcurrencyConflict;
use Illuminate\Http\JsonResponse;
use Payroll\Application\Exception\EarningLineNotFound;
use Payroll\Application\Exception\UnknownCurrency;
use Payroll\Domain\Exception\PayrollException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns the refusals of Payroll into HTTP answers (ADR 0014): 404 for a line that does not exist, 409 when the request
 * meets a state it was not made for, 422 when the request breaks a rule. Anything else is a fault and is thrown.
 */
final class Responses
{
    /** @param \Closure(): Response $work */
    public static function of(\Closure $work): Response
    {
        try {
            return $work();
        } catch (EarningLineNotFound $notFound) {
            return self::error(Response::HTTP_NOT_FOUND, $notFound);
        } catch (ConcurrencyConflict|CommandIdReused $conflict) {
            return self::error(Response::HTTP_CONFLICT, $conflict);
        } catch (PayrollException|UnknownCurrency|InvalidRequest $invalid) {
            return self::error(Response::HTTP_UNPROCESSABLE_ENTITY, $invalid);
        }
    }

    private static function error(int $status, \Throwable $refusal): JsonResponse
    {
        return new JsonResponse(['error' => $refusal->getMessage()], $status);
    }
}
