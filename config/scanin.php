<?php

return [
    /*
     | Public sign-up is closed by default. Turn it on briefly in the server .env
     | (SCANIN_REGISTRATION_OPEN=true) to let a named agent register, then turn it
     | off again. New agents still need approval (agents.approved_at) before they
     | can create open houses.
     */
    'registration_open' => (bool) env('SCANIN_REGISTRATION_OPEN', false),

    /*
     | Shown on the public privacy and terms pages. Set in the server .env.
     | Without a contact email the pages say to reach ScanIn through the agent.
     */
    'operator_name' => env('SCANIN_OPERATOR_NAME', 'ScanIn'),
    'contact_email' => env('SCANIN_CONTACT_EMAIL'),
    'mailing_address' => env('SCANIN_MAILING_ADDRESS'),
];
