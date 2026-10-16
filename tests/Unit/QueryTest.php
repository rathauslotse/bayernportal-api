<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Http\Query;

enum QueryTestMode: string
{
    case Direct = 'DIREKT';
}

it('serialises booleans, enums and drops nulls', function (): void {
    expect(Query::normalise([
        'size' => 50,
        'full' => true,
        'gruppiertNachLeistungen' => false,
        'gemeindekennziffer' => null,
        'mode' => QueryTestMode::Direct,
    ]))->toBe([
        'full' => 'true',
        'gruppiertNachLeistungen' => 'false',
        'mode' => 'DIREKT',
        'size' => 50,
    ]);
});
