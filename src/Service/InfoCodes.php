<?php

namespace App\Service;

abstract class InfoCodes
{
    public const array USER = [
        'INVALID_AGENCY_KEY' => 'AGEKEY1',
        'USR_NOT_FOUND' => 'USRNTFD',
        'USER_AUTH_NOT_FOUND' => 'USRANFD',
        'USR_FORBIDDEN' => 'USRFBDN',
        'LOCKED_ACCOUNT' => 'USRLOA0',
        'TOO_MANY_REQUESTS_IP' => 'TMREQU0',
        'TOO_MANY_REQUESTS_EMAIL' => 'TMREQU1',
    ];

    public const array ACCOUNT_VALIDATION = [
        'EMAIL_TOKEN_EXPIRED' => 'USRACT1',
        'EMAIL_TOKEN_INVALID' => 'USRACT0',
        'USER_NOT_FOUND_WITH_TOKEN' => 'USNFWT0',
    ];

    public const array RESET_PASSWORD = [
        'EMAIL_TOKEN_EXPIRED' => 'USRACT1',
        'EMAIL_TOKEN_INVALID' => 'USRACT0',
        'USER_NOT_FOUND_WITH_TOKEN' => 'USNFWT0',
    ];

    public const array JWT = [
        'BAD_CREDENTIALS' => 'JWTBAD0',
        'INVALID_TOKEN' => 'JWTINV0',
        'MISSING_TOKEN' => 'JWTMIS0',
        'EXPIRED_TOKEN' => 'JWTEXP0',
    ];

    public const array INTERNAL = [
        'FILE_CACHE_NO_CONTEXT' => 'IFILCC0',
        'INVALID_DATA' => 'INVDAT0',
        'INVALID_INPUT' => 'INVINP0',
        'UNSUPPORTED_OPERATION' => 'UNSOPR0',
        'DATABASE_ERROR' => 'DBERROR',
        'PROCESSOR_FAILED' => 'PRCFAIL0',
        'ENTITY_NOT_FOUND' => 'ENTNFND0',
        'OPERATION_REQUIRED' => 'OPRREQ0',
        'INVALID_DATE_FORMAT' => 'INVDATF0',
        'ERROR_SERVER' => 'ERRSER0',
    ];
}
