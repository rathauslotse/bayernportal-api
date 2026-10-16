<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Resources;

use Illuminate\Support\Collection;
use Rathauslotse\BayernPortal\Data\LifeSituation;
use Rathauslotse\BayernPortal\Data\LifeSituationNode;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;
use Rathauslotse\BayernPortal\Support\Validate;

/**
 * Life situations (Lebenslagen), a two-level category tree for services.
 *
 * REST resource: `lebenslagen`.
 */
final class LifeSituationResource extends Resource
{
    private const array LIST = ['lebenslagen', 'lebenslage'];

    /**
     * All life situations as a flat list, sorted by id as returned.
     *
     * `GET lebenslagen?leistungenZugeordnet=`
     *
     * @param  bool|null  $onlyWithServices  Only life situations with assigned services (leistungenZugeordnet); null uses the API default.
     * @return Collection<int, LifeSituation>
     *
     * @throws BayernPortalException
     */
    public function list(?bool $onlyWithServices = null): Collection
    {
        return $this->many('lebenslagen', ['leistungenZugeordnet' => $onlyWithServices], self::LIST, LifeSituation::fromPayload(...));
    }

    /**
     * All life situations as a tree of top-level categories and their
     * sub-categories. Sub-categories whose parent is missing are returned
     * as top-level nodes.
     *
     * @return Collection<int, LifeSituationNode>
     *
     * @throws BayernPortalException
     */
    public function tree(?bool $onlyWithServices = null): Collection
    {
        return self::buildTree($this->list($onlyWithServices));
    }

    /**
     * A single life situation with its services.
     *
     * `GET lebenslagen/{lebenslage-id}`
     *
     * @throws BayernPortalException
     */
    public function find(string $lifeSituationId): LifeSituation
    {
        return $this->one('lebenslagen/'.Validate::lifeSituationId($lifeSituationId), [], self::LIST, LifeSituation::fromPayload(...));
    }

    /**
     * @param  Collection<int, LifeSituation>  $lifeSituations
     * @return Collection<int, LifeSituationNode>
     */
    public static function buildTree(Collection $lifeSituations): Collection
    {
        $ids = $lifeSituations->map(fn (LifeSituation $situation): string => $situation->id)->flip();
        $children = $lifeSituations
            ->filter(fn (LifeSituation $situation): bool => $situation->parentId !== null && $ids->has($situation->parentId))
            ->groupBy(fn (LifeSituation $situation): string => (string) $situation->parentId);

        return $lifeSituations
            ->reject(fn (LifeSituation $situation): bool => $situation->parentId !== null && $ids->has($situation->parentId))
            ->map(fn (LifeSituation $situation): LifeSituationNode => new LifeSituationNode(
                $situation,
                array_values($children->get($situation->id, new Collection)->all()),
            ))
            ->values();
    }
}
