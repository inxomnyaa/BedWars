<?php

namespace xenialdan\BedWars;

use xenialdan\gameapi\Team;

class BedwarsTeam extends Team
{
    /** @var bool */
    private $bedDestroyed = false;

    /**
     * @return bool
     */
    public function isBedDestroyed(): bool
    {
        return $this->bedDestroyed;
    }

    /**
     * @param bool $bedDestroyed
     */
    public function setBedDestroyed(bool $bedDestroyed = true): void
    {
        $this->bedDestroyed = $bedDestroyed;
    }
}
