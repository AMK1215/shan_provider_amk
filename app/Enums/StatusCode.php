<?php

namespace App\Enums;

enum StatusCode: int
{
    case OK = 200;
    case InvalidPlayerPassword = 900404;
    case OperatorIDError = 900405;
    case IncomingRequestIncomplete = 900406;
    case InvalidSignature = 900407;
    case DuplicateTransaction = 900409;
    case BetTransactionNotFound = 900415;
    case PlayerInactive = 900416;
    case InsufficientBalance = 900605;
    case InternalServerError = 900500;
    case MaxPayoutReached = 800401;
    case InvalidPlayer = 900402;
    case BadRequest = 400;
    case ForbiddenAccess = 403;
    case ServiceMaintenance = 503;
    case InvalidOperatorID = 900401;
    case NotEligibleCancel = 900300;
    case InvalidTranID = 900408;
    case WalletNotFound = 900127;
    case WalletUpdateFailed = 900128;

}
