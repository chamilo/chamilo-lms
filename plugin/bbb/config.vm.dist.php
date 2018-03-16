<?php

/**
 * DO vm_min_size_id/vm_max_size_id sizes.
 *
 * ID    Name
 * 66    512MB
 * 63    1GB
 * 62    2GB
 * 64    4GB
 * 65    8GB
 * 61    16GB
 * 60    32GB
 * 70    48GB
 * 70    48GB
 * 69    64GB
 * 68    96GB
 */

return [
    'enabled' => true,
    'vms' => [
        [
            'enabled' => true,
            'name' => 'DigitalOcean',
            'vm_client_id' => 'client_id',
            'api_key' => '123456',
            'vm_id' => '123456', // The VM ID we want to access
            'vm_min_size_id' => '66', // VM size ID example for 512mb use 66
            'vm_max_size_id' => '65', // For 1GB use 63
        ],
        // The Amazon hook is not implemented yet
        [
            'enabled' => false,
            'name' => 'Amazon',
        ],
    ],
];
