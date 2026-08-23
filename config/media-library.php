<?php

use App\Services\MediaPathGenerator;

return [

    /*
    |--------------------------------------------------------------------------
    | Path Generator
    |--------------------------------------------------------------------------
    |
    | Where a media file lands on its disk. Ours groups the files under the
    | model they belong to — `exercises/{exercise}/{media}/file.jpg` — rather
    | than the package default of `{media}/file.jpg`.
    |
    | This file is deliberately partial: every other key still comes from the
    | package's own configuration, which the service provider merges in.
    |
    */

    'path_generator' => MediaPathGenerator::class,

];
