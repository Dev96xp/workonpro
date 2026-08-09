<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plan Limits
    |--------------------------------------------------------------------------
    |
    | Límite de recursos que puede crear un tenant según su plan. Un valor
    | null significa ilimitado. Cambiar estos números no requiere tocar
    | ningún componente: images.blade.php, services.blade.php, coupons.blade.php
    | y ImageController leen todos de acá.
    |
    */

    'limits' => [
        'basic' => [
            'images' => 20,
            'services' => 3,
            'coupons' => 2,
        ],
        'pro' => [
            'images' => 100,
            'services' => null,
            'coupons' => null,
        ],
        'enterprise' => [
            'images' => null,
            'services' => null,
            'coupons' => null,
        ],
    ],

];
