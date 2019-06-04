<?php


namespace xenialdan\BedWars;


use pocketmine\block\BlockIds;
use xenialdan\gameapi\DefaultSettings;

class BedwarsSettings extends DefaultSettings
{
    public $noDamageTeam = true;
    public $noEnvironmentDamage = true;
    public $clearInventory = true;
    public $noBlockDrops = false;
    public $immutableWorld = false;
    public $noBreak = true;
    public $noBuild = true;
    public $breakBlockIds = [BlockIds::SANDSTONE, BlockIds::END_STONE, BlockIds::CHEST, BlockIds::ENDER_CHEST, BlockIds::BED_BLOCK];
    public $placeBlockIds = [BlockIds::SANDSTONE, BlockIds::END_STONE, BlockIds::CHEST, BlockIds::ENDER_CHEST, BlockIds::BED_BLOCK];
    public $noBed = true;
    public $startNoWalk = false;
    public $bronze = [];
    public $silver = [];
    public $gold = [];
    public $noDropItem = false;
}