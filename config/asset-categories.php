<?php

/**
 * Asset Category → Type → Brand cascading data map.
 * Used by both Blade views and JS for cascading dropdowns.
 */
return [
    'categories' => [
        'office_furniture'   => 'Office Furniture',
        'it_equipment'       => 'IT Equipment / Hardware',
        'office_equipment'   => 'Office Equipment',
        'software'           => 'Software',
        'office_supplies'    => 'Office Supplies / Current Assets',
        'leasehold'          => 'Leasehold Improvements',
        'others'             => 'Others',
    ],

    // Category key → array of type options
    'types' => [
        'office_furniture' => [
            'desk'            => 'Desk',
            'chair'           => 'Chair',
            'filing_cabinet'  => 'Filing Cabinet',
            'table'           => 'Table',
            'bookshelf'       => 'Bookshelf',
            'sofa'            => 'Sofa',
        ],
        'it_equipment' => [
            'desktop'  => 'Desktop',
            'laptop'   => 'Laptop',
            'server'   => 'Server',
            'router'   => 'Router',
            'printer'  => 'Printer',
            'monitor'  => 'Monitor',
            'scanner'  => 'Scanner',
            'phone'    => 'Phone',
            'sim_card' => 'SIM Card',
            'access_card' => 'Access Card',
            'converter'   => 'Converter / Adapter',
            'accessories' => 'Accessories',
        ],
        'office_equipment' => [
            'photocopier'      => 'Photocopier',
            'fax_machine'      => 'Fax Machine',
            'shredder'         => 'Shredder',
            'telephone_system' => 'Telephone System',
        ],
        'software' => [
            'license'      => 'License',
            'subscription' => 'Subscription',
            'specialized'  => 'Specialized Software',
        ],
        'office_supplies' => [
            'paper'      => 'Paper',
            'stationery' => 'Stationery',
            'toner'      => 'Toner / Ink',
            'consumable' => 'Other Consumable',
        ],
        'leasehold' => [
            'carpet'    => 'Carpet / Flooring',
            'lighting'  => 'Lighting',
            'partition' => 'Partition Wall',
            'renovation'=> 'Other Renovation',
        ],
        'others' => [
            'other' => 'Other',
        ],
    ],

    // Type key → brand options. 'text' means free-text input, array means dropdown.
    'brands' => [
        // IT Equipment — dropdown brands
        'desktop'  => ['Dell', 'HP', 'Lenovo', 'Apple', 'Asus', 'Acer', 'MSI', 'Intel NUC', 'Other'],
        'laptop'   => ['Dell', 'HP', 'Lenovo', 'Apple', 'Asus', 'Acer', 'MSI', 'Samsung', 'Microsoft', 'Huawei', 'Other'],
        'server'   => ['Dell', 'HP Enterprise', 'Lenovo', 'Supermicro', 'Cisco', 'IBM', 'Fujitsu', 'Other'],
        'router'   => ['Cisco', 'TP-Link', 'Netgear', 'Ubiquiti', 'MikroTik', 'Huawei', 'Juniper', 'D-Link', 'Other'],
        'printer'  => ['HP', 'Canon', 'Epson', 'Brother', 'Xerox', 'Lexmark', 'Ricoh', 'Kyocera', 'Other'],
        'monitor'  => ['Dell', 'HP', 'Lenovo', 'LG', 'Samsung', 'Asus', 'Acer', 'BenQ', 'AOC', 'ViewSonic', 'Philips', 'Other'],
        'scanner'  => ['Fujitsu', 'Canon', 'Epson', 'Brother', 'HP', 'Plustek', 'Other'],
        'phone'    => ['Apple', 'Samsung', 'Huawei', 'Xiaomi', 'Oppo', 'Vivo', 'OnePlus', 'Google', 'Sony', 'Nokia', 'Other'],
        'sim_card' => ['Maxis', 'Digi', 'Celcom', 'U Mobile', 'Yes 4G', 'Unifi Mobile', 'Other'],
        'access_card' => ['HID', 'Suprema', 'ZKTeco', 'Keri Systems', 'Other'],
        'converter'   => ['Anker', 'Ugreen', 'Baseus', 'Belkin', 'HyperDrive', 'Satechi', 'Other'],
        'accessories' => ['Logitech', 'Jabra', 'Anker', 'Microsoft', 'Apple', 'Baseus', 'Targus', 'Kensington', 'Other'],

        // Everything else uses free-text input (return 'text')
    ],
];
