<?php


namespace App\Models\DataTables;


class ItemWeapon extends DataTableModel
{
    protected $table = 'datatable_item_weapon';

    public function selectAll()
    {
        return $this->select([
            'classid',
            'itemname',
            'itemclass',
            'filename',
            'desc',
            'price',
            'sellprice',
            'fesoprice',
            'tradable',
            'venderable',
            'enchantlv',
            'promotionlv',
            'wlv',
            'atk',
            'ar',
            'imp',
            'fireip',
            'iceip',
            'lgtip',
            'psyip',
            'defip',
        ]);
    }
}