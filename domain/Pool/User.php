<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competition;
use SportsHelpers\Identifiable;
use SuperElf\Achievement\BadgeCategory;
use SuperElf\ChatMessage;
use SuperElf\ChatMessage\Unread as UnreadChatMessage;
use SuperElf\Competitor;
use SuperElf\Formation;
use SuperElf\Periods\AssemblePeriod;
use SuperElf\Periods\TransferPeriod;
use SuperElf\Periods\ViewPeriod;
use SuperElf\Points;
use SuperElf\Pool;
use SuperElf\Replacement;
use SuperElf\Substitution;
use SuperElf\Totals;
use SuperElf\Totals\Calculator as TotalsCalculator;
use SuperElf\Transfer;
use SuperElf\User as BaseUser;

class User extends Identifiable
{
    protected bool $admin;
    protected Formation|null $assembleFormation = null;
    protected Formation|null $transferFormation = null;
    /**
     * @var Collection<int|string, Replacement>
     */
    protected Collection $replacements;
    /**
     * @var Collection<int|string, Transfer>
     */
    protected Collection $transfers;
    /**
     * @var Collection<int|string, Substitution>
     */
    protected Collection $substitutions;
    /**
     * @var Collection<int|string, Competitor>
     */
    protected Collection $competitors;
    /**
     * @var Collection<int|string, ChatMessage>
     */
    protected Collection $chatMessages;
    /**
     * @var Collection<int|string, UnreadChatMessage>
     */
    protected Collection $unreadChatMessages;
//    /**
//     * @var ArrayCollection<int|string, GameRoundScore>|PersistentCollection<int|string, GameRoundScore>
//     * @psalm-var ArrayCollection<int|string, GameRoundScore>|
//     */
//    protected ArrayCollection|PersistentCollection $scores;

    public function __construct(protected Pool $pool, protected BaseUser $user)
    {
        $this->admin = false;
        $this->competitors = new ArrayCollection();
        $this->replacements = new ArrayCollection();
        $this->transfers = new ArrayCollection();
        $this->substitutions = new ArrayCollection();
        $this->chatMessages = new ArrayCollection();
        $this->unreadChatMessages = new ArrayCollection();
        // $this->scores = new ArrayCollection();
        if (!$pool->getUsers()->contains($this)) {
            $pool->getUsers()->add($this);
        }
    }

    public function getPool(): Pool
    {
        return $this->pool;
    }

    public function getUser(): BaseUser
    {
        return $this->user;
    }

    public function getAdmin(): bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): void
    {
        $this->admin = $admin;
    }

    /**
     * @return Collection<int|string, Competitor>
     */
    public function getCompetitors(): Collection
    {
        return $this->competitors;
    }

    public function getCompetitor(Competition $competition): Competitor|null
    {
        $filtered = $this->competitors->filter(function (Competitor $competitor) use ($competition): bool {
            return $competitor->getCompetition() === $competition;
        });
        $firstCompetitor = $filtered->first();
        return $firstCompetitor === false ? null : $firstCompetitor;
    }

    /**
     * @return Collection<int|string, Replacement>
     */
    public function getReplacements(): Collection
    {
        return $this->replacements;
    }

    /**
     * @return Collection<int|string, Transfer>
     */
    public function getTransfers(): Collection
    {
        return $this->transfers;
    }

    /**
     * @return Collection<int|string, Substitution>
     */
    public function getSubstitutions(): Collection
    {
        return $this->substitutions;
    }

    public function getAssembleFormation(): Formation|null
    {
        return $this->assembleFormation;
    }

    public function setAssembleFormation(Formation $formation = null): void
    {
        $this->assembleFormation = $formation;
    }

    public function getTransferFormation(): ?Formation
    {
        return $this->transferFormation;
    }

    public function setTransferFormation(Formation $formation): void
    {
        $this->transferFormation = $formation;
    }

    public function getNrOfAssembled(): int
    {
        $formation = $this->getAssembleFormation();
        return $formation !== null ? $formation->getNrOfPersons() : 0;
    }

    public function getNrOfTransfers(): int
    {
        return count($this->getTransfers());
    }

//    /**
//     * @return ArrayCollection<int|string, GameRoundScore>|PersistentCollection<int|string, GameRoundScore>
//     */
//    public function getScores(): ArrayCollection|PersistentCollection
//    {
//        return $this->scores;
//    }

    /**
     * @return Collection<int|string, ChatMessage>
     */
    public function getChatMessages(): Collection
    {
        return $this->chatMessages;
    }

    /**
     * @return Collection<int|string, UnreadChatMessage>
     */
    public function getUnreadChatMessages(): Collection
    {
        return $this->unreadChatMessages;
    }

    public function getFormation(AssemblePeriod|TransferPeriod|ViewPeriod $editOrViewPeriod): Formation|null
    {
        if ($editOrViewPeriod instanceof AssemblePeriod) {
            return $this->getAssembleFormation();
        } else if ($editOrViewPeriod instanceof TransferPeriod) {
            return $this->getTransferFormation();
        }
        $assembleViewPeriod = $this->getPool()->getAssemblePeriod()->getViewPeriod()->getPeriod();
        if ($editOrViewPeriod->getPeriod()->equals($assembleViewPeriod)) {
            return $this->getAssembleFormation();
        }
        $transferViewPeriod = $this->getPool()->getTransferPeriod()->getViewPeriod()->getPeriod();
        if ($editOrViewPeriod->getPeriod()->equals($transferViewPeriod)) {
            return $this->getTransferFormation();
        }
        return null;
    }

    public function getTotalPoints(Points $s11Points, BadgeCategory|null $badgeCategory): int
    {
        $points = 0;
        $assembleFormation = $this->getAssembleFormation();
        if( $assembleFormation !== null) {
            $points += $assembleFormation->getTotalPoints($s11Points, $badgeCategory);
        }

        $transferFormation = $this->getTransferFormation();
        if( $transferFormation !== null) {
            $points += $transferFormation->getTotalPoints($s11Points, $badgeCategory);
        }
        return $points;
    }

    public function canCompete(): bool
    {
        $assembleFormation = $this->getAssembleFormation();
        return $assembleFormation !== null && $assembleFormation->allPlacesHaveAPlayer();
    }
}
