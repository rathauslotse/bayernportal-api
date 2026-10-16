<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Enums;

/**
 * Target group of a life situation.
 */
enum LifeSituationCategory: string
{
    /** API value "buerger" */
    case Citizen = 'buerger';

    /** API value "unternehmer" */
    case Business = 'unternehmer';

    /** API value "verwaltung" */
    case Administration = 'verwaltung';
}
