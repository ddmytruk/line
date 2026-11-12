<?php

declare(strict_types=1);

namespace Payroll\Domain\Exception;

/** A business rule of the Payroll domain was violated. The message is safe to show to a user. */
abstract class PayrollException extends \DomainException {}
