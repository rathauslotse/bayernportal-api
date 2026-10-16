<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

/**
 * A top-level life situation with its sub-categories.
 */
final readonly class LifeSituationNode
{
    /**
     * @param  list<LifeSituation>  $children
     */
    public function __construct(
        public LifeSituation $lifeSituation,
        public array $children = [],
    ) {}
}
