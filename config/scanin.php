<?php

return [
    /*
     | Public sign-up is closed by default. Turn it on briefly in the server .env
     | (SCANIN_REGISTRATION_OPEN=true) to let a named agent register, then turn it
     | off again. New agents still need approval (agents.approved_at) before they
     | can create open houses.
     */
    'registration_open' => (bool) env('SCANIN_REGISTRATION_OPEN', false),
];
