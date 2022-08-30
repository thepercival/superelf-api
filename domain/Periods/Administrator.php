<?php

declare(strict_types=1);

namespace SuperElf\Periods;

use Selective\Config\Configuration;
use SuperElf\ActiveConfig\Service as ActiveConfigService;
use SuperElf\Periods\AssemblePeriod\Repository as AssemblePeriodRepository;
use SuperElf\Periods\TransferPeriod\Repository as TransferPeriodRepository;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;
use SuperElf\Pool\Repository as PoolRepository;

class Administrator
{
    public function __construct(
        protected PoolRepository $poolRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected AssemblePeriodRepository $assemblePeriodRepos,
        protected TransferPeriodRepository $transferPeriodRepos,
        protected ActiveConfigService $activeConfigService,
        protected Configuration $config
    ) {
    }
/*
    public function getCreateAndJoinPeriod(Competition $sourceCompetition): ViewPeriod
    {
        $period = $this->activeConfigService->getCreateAndJoinPeriod();
        return $this->createViewPeriod($sourceCompetition, $period);
    }

    protected function createViewPeriod(Competition $sourceCompetition, Period $period): ViewPeriod
    {
        $viewPeriod = $this->viewPeriodRepos->findOneBy([
            "sourceCompetition" => $sourceCompetition, "startDateTime" => $period->getStartDate()]);
        if ($viewPeriod !== null) {
            return $viewPeriod;
        }
        $viewPeriod = new ViewPeriod($sourceCompetition, $period);
        return $this->viewPeriodRepos->save($viewPeriod);
    }

    public function getAssemblePeriod(Competition $sourceCompetition): AssemblePeriod
    {
        $period = $this->activeConfigService->getAssemblePeriod();
        $assemblePeriod = $this->assemblePeriodRepos->findOneBy([
            "sourceCompetition" => $sourceCompetition, "startDateTime" => $period->getStartDate() ]);
        if ($assemblePeriod !== null) {
            return $assemblePeriod;
        }

        $assembleViewPeriod = $this->activeConfigService->getAssembleViewPeriod();
        $assemblePeriod = new AssemblePeriod(
            $sourceCompetition,
            $period,
            $this->createViewPeriod($sourceCompetition, $assembleViewPeriod)
        );

        return $this->assemblePeriodRepos->save($assemblePeriod);
    }

    public function getTransferPeriod(Competition $sourceCompetition): TransferPeriod
    {
        $period = $this->activeConfigService->getTransferPeriod();
        $transferPeriod = $this->transferPeriodRepos->findOneBy(
            ["sourceCompetition" => $sourceCompetition, "startDateTime" => $period->getStartDate() ]
        );
        if ($transferPeriod !== null) {
            return $transferPeriod;
        }

        $transferViewPeriod = $this->activeConfigService->getTransferViewPeriod();
        $transferPeriod = new TransferPeriod(
            $sourceCompetition,
            $period,
            $this->createViewPeriod($sourceCompetition, $transferViewPeriod),
            Defaults::MAXNROFTRANSFERS
        );

        return $this->transferPeriodRepos->save($transferPeriod);
    }*/
}
