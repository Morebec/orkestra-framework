<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\Projection;

use Morebec\Orkestra\EventSourcing\Projection\ProjectorGroup;

class PostgreSqlProjectorGroup extends ProjectorGroup
{
    public function __construct(iterable $projectors = [])
    {
        parent::__construct('postgresql-projector', $projectors);
    }
}