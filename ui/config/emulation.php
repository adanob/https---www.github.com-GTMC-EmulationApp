<?php

return [
    /*
    |--------------------------------------------------------------------------
    | EmulationApp Root Path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the EmulationApp directory where engine/, payloads/,
    | scripts/, and .emulation_key live.
    |
    | On your machine this is likely: C:\wamp64\www\EmulationApp
    |
    */
    'app_root' => env('EMULATION_APP_ROOT', base_path('../')),

    'payloads_dir' => env('EMULATION_PAYLOADS_DIR', base_path('../payloads')),

    'scripts_dir' => env('EMULATION_SCRIPTS_DIR', base_path('../scripts')),
];
