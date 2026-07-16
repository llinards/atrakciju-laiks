<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded Admin Password
    |--------------------------------------------------------------------------
    |
    | Initial password for the admin user created by DatabaseSeeder. Set
    | SEED_ADMIN_PASSWORD in .env before seeding production; whatever the
    | value, change it after the first login.
    |
    */

    'admin_password' => env('SEED_ADMIN_PASSWORD', 'password'),

];
