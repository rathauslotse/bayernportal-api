<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Enums;

/**
 * Which contact persons to include with responsible authorities of a
 * service description (API parameter "behoerdenAnsprechpartnerZuordnungen").
 */
enum ContactAssignmentMode: string
{
    /** Contacts assigned directly to the service; if there are none, all contacts. */
    case PreferDirect = 'DIREKTBEVORZUGT';

    /** Only contacts assigned directly to the service. */
    case Direct = 'DIREKT';

    /** All contacts of the authority. */
    case All = 'ALLE';

    /** No contacts. */
    case None = 'NEIN';
}
