<?php

namespace App\Exceptions;

use DomainException;

/**
 * Thrown by ReservationApprovalService when an approve/reject/cancel
 * action fails a business-rule check (PRD 7.3, 7.4). The message is
 * always a user-facing Indonesian explanation.
 */
class ReservationApprovalException extends DomainException
{
}
