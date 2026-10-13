<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Personuppgiftsansvarig
    |--------------------------------------------------------------------------
    |
    | Who is responsible for the personal data, shown in the privacy policy
    | (/integritet). Set on the server, never in the code.
    |
    */

    'controller' => env('PRIVACY_CONTROLLER'),

    'contact' => env('PRIVACY_CONTACT'),

];
