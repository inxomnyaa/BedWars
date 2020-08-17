<?php

declare(strict_types=1);

namespace xenialdan\BedWars\commands\arguments;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use xenialdan\BedWars\Loader;
use xenialdan\gameapi\Arena;
use xenialdan\gameapi\Game;

class JoinableArenaEnumArgument extends StringEnumArgument
{
    public function getTypeName(): string
    {
        return "string";
    }

    public function parse(string $argument, CommandSender $sender)
    {
        return $argument;
    }

    public function getEnumValues(): array
    {
        /** @var Game $p */
        $p = Loader::getInstance();
        $arenas = $p->getArenas();

        var_dump(array_values(array_map(function (Arena $arena): string {
            return $arena->getLevelName();
        }, array_filter($arenas, function (Arena $arena): bool {
            return ($arena->getState() === Arena::IDLE || $arena->getState() === Arena::WAITING);
        }))));
        return array_values(array_map(function (Arena $arena): string {
            return $arena->getLevelName();
        }, array_filter($arenas, function (Arena $arena): bool {
            return ($arena->getState() === Arena::IDLE || $arena->getState() === Arena::WAITING);
        })));
    }

    public function getEnumName(): string
    {
        return "Arena";
    }
}
