<?php

return [
    // Default route authorization
    'authorized' => [
        'index',
        'get',
    ],

    // Default limits
    'limits' => [
        'pages' => [5, 10, 25, 50, 100],
    ],

    // Models by routes + base restriction, format:
    //   [resource_name] => [resource_model]
    'routes' => [
        'item_achieve' => 'App\Models\DataTables\ItemAchieve',
        'item_armor' => 'App\Models\DataTables\ItemArmor',
        'item_artifact' => 'App\Models\DataTables\ItemArtifact',
        'item_back' => 'App\Models\DataTables\ItemBack',
        'item_belt' => 'App\Models\DataTables\ItemBelt',
        'item_boot' => 'App\Models\DataTables\ItemBoot',
        'item_consume' => 'App\Models\DataTables\ItemConsume',
        'item_costume' => 'App\Models\DataTables\ItemCostume',
        'item_earring' => 'App\Models\DataTables\ItemEarring',
        'item_etc' => 'App\Models\DataTables\ItemEtc',
        'item_face' => 'App\Models\DataTables\ItemFace',
        'item_glove' => 'App\Models\DataTables\ItemGlove',
        'item_head' => 'App\Models\DataTables\ItemHead',
        'item_neck' => 'App\Models\DataTables\ItemNeck',
        'item_quest' => 'App\Models\DataTables\ItemQuest',
        'item_recipe' => 'App\Models\DataTables\ItemRecipe',
        'item_ring' => 'App\Models\DataTables\ItemRing',
        'item_scroll' => 'App\Models\DataTables\ItemScroll',
        'item_weapon' => 'App\Models\DataTables\ItemWeapon',
    ],
];