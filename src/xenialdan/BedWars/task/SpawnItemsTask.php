<?php

namespace xenialdan\BedWars\task;

use pocketmine\scheduler\Task;
use xenialdan\BedWars\Loader;
use xenialdan\gameapi\Arena;

class SpawnItemsTask extends Task
{
    /** @var Arena */
    private $arena;

    public function __construct(Arena $arena)
    {
        $this->arena = $arena;
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick)
    {
        if ($this->arena->getState() === Arena::INGAME) {
            if ($currentTick % 50 === 0) Loader::getInstance()->spawnBronze($this->arena);
            if ($currentTick % 600 === 0) Loader::getInstance()->spawnSilver($this->arena);
            if ($currentTick % 1200 === 0) Loader::getInstance()->spawnGold($this->arena);
        } else {
            $this->getHandler()->cancel();
        }
    }
}