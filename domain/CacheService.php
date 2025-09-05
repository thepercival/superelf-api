<?php

declare(strict_types=1);

namespace SuperElf;

use JMS\Serializer\SerializerInterface;
use Memcached;
use SuperElf\GameRound\TotalsCalculator;
use SuperElf\Periods\ViewPeriod;

final class CacheService
{
    public const GameRoundFormationLinesTotalsPrefix = 'json-gameRound-formLines-totals';
    public const CacheTime = 86400;

    public function __construct(
        private SerializerInterface $serializer,
        private MemCached $memCached,
        private string $namespace)
    {
    }

    public function getViewPeriodTotals(Pool $pool, ViewPeriod $viewPeriod): string {

        $cacheId = $this->getTotalsCacheId($pool, $viewPeriod, null);
        /** @var string|false $memCachedItem */
        $memCachedItem = $this->memCached->get($cacheId);
        if( $memCachedItem === false ) {
            $totals = (new TotalsCalculator())->calculatePoolUsers($pool, $viewPeriod, null);
            $json = $this->serializer->serialize($totals, 'json');
            $this->memCached->set($cacheId, $json, self::CacheTime );
            return $json;
        }
        return $memCachedItem;
    }

    public function getGameRoundTotals(Pool $pool, GameRound $gameRound): string {

        $cacheId = $this->getTotalsCacheId($pool, $gameRound->getViewPeriod(), $gameRound);
        /** @var string|false $memCachedItem */
        $memCachedItem = $this->memCached->get($cacheId);
        if( $memCachedItem === false ) {
            $totals = (new TotalsCalculator())->calculatePoolUsers($pool, $gameRound->getViewPeriod(), $gameRound);
            $json = $this->serializer->serialize($totals, 'json');
            $this->memCached->set($cacheId, $json, self::CacheTime );
            return $json;
        }
        return $memCachedItem;
    }

    public function resetTotals(Pool $pool, GameRound $gameRound): void
    {
        $viewPeriodCacheId = $this->getTotalsCacheId($pool, $gameRound->getViewPeriod(), null);
        $this->memCached->delete($viewPeriodCacheId);
        $gameRoundCacheId = $this->getTotalsCacheId($pool, $gameRound->getViewPeriod(), $gameRound);
        $this->memCached->delete($gameRoundCacheId);
    }

    private function getTotalsCacheId(Pool $pool, ViewPeriod $viewPeriod, GameRound|null $gameRound): string
    {
        $cacheId = $this->namespace . '-' .
            self::GameRoundFormationLinesTotalsPrefix . '-' .
            ((string)$pool->getId()) . '-' . (string)$viewPeriod->getId();
        if( $gameRound !== null) {
            $cacheId .= '-' . $gameRound->getNumber();
        }
        return $cacheId;
    }
}
