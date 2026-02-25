<?php

return [
    /*
    |--------------------------------------------------------------------------
    | EmulationApp Root Path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the EmulationApp directory where engine/, jobs/,
    | and .emulation_key live.
    |
    | On your machine this is likely: C:\wamp64\www\EmulationApp
    |
    */
    'app_root' => env('EMULATION_APP_ROOT', base_path('../')),

    'jobs_dir' => env('EMULATION_JOBS_DIR', base_path('../jobs')),
];
