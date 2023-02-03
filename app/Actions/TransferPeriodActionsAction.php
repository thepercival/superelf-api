<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\Person\Repository as PersonRepository;
use Sports\Team\Player\Repository as PlayerRepository;
use SuperElf\Formation\Place\Repository as FormationPlaceRepository;
use SuperElf\Formation\Repository as FormationRepository;
use SuperElf\Transfer\Repository as TransferRepository;
use SuperElf\OneTeamSimultaneous;
use SuperElf\Replacement\Repository as ReplacementRepository;
use SuperElf\Formation\Validator as FormationValidator;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Player\Syncer as S11PlayerSyncer;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\Replacement;

final class TransferPeriodActionsAction extends Action
{
    protected FormationValidator $formationValidator;
    protected OneTeamSimultaneous $oneTeamSimultaneous;

    public function __construct(
        protected PoolUserRepository $poolUserRepos,
        protected FormationRepository $formationRepos,
        protected FormationPlaceRepository $formationPlaceRepos,
        protected ReplacementRepository $replacementRepos,
        protected PersonRepository $personRepos,
        protected PlayerRepository $playerRepos,
        protected TransferRepository $transferRepos,
        protected S11PlayerRepository $s11PlayerRepos,
        protected S11PlayerSyncer $s11PlayerSyncer,
        protected Configuration $config,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
        $this->formationValidator = new FormationValidator($config);
        $this->oneTeamSimultaneous = new OneTeamSimultaneous();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function replace(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            $transferPeriod = $poolUser->getPool()->getTransferPeriod();

            if (!$transferPeriod->contains()) {
                throw new \Exception("je kan alleen een vervanging tijdens de transferperiode doen");
            }

            $formation = $poolUser->getAssembleFormation();
            if ($formation === null) {
                throw new \Exception("je hebt geen formatie gekozen");
            }

            $rawData = $this->getRawData();
            $serReplacement = null; $playerIn = null;
            if (strlen($rawData) > 0) {
                /** @var Replacement $serReplacement */
                $serReplacement = $this->serializer->deserialize(
                    $rawData,
                    Replacement::class,
                    'json'
                );
                $playerIn = $this->playerRepos->find($serReplacement->getPlayerIn()->getId());
            }
            if ($serReplacement === null || $playerIn === null) {
                throw new \Exception("de vervanger is niet gevuld");
            }

            // 1 check transferactions
            if ($poolUser->getTransfers()->count() > 0) {
                throw new \Exception("je hebt al transfers gedaan en kunt geen vervanging meer doen");
            }
            if ($poolUser->getSubstitutions()->count() > 0) {
                throw new \Exception("je hebt al wissels gedaan en kunt geen vervanging meer doen");
            }

            $replacement = new Replacement(
                $poolUser,
                $transferPeriod,
                $serReplacement->getLineNumberOut(),
                $serReplacement->getPlaceNumberOut(),
                $playerIn
            );

            // 3 check if new action creates valid formation
            $this->formationValidator->validateTransferActions( $poolUser );

            // 4 add to replacements
            $this->replacementRepos->save($replacement, true);

            $json = $this->serializer->serialize($replacement, 'json', $this->getSerializationContext(['person']));
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function removeReplacement(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            if( !$poolUser->getPool()->getTransferPeriod()->contains() ) {
                throw new \Exception("je kan alleen een vervanging doen tijdens de transferperiode");
            }

            $replacement = $this->replacementRepos->find($args["id"]);
            if ($replacement === null) {
                throw new \Exception("de vervanging is niet gevonden", E_ERROR);
            }

            $poolUser->getReplacements()->removeElement($replacement);
            $this->replacementRepos->remove($replacement);
            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function removeTransfer(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            if( !$poolUser->getPool()->getTransferPeriod()->contains() ) {
                throw new \Exception("je kan alleen een transfer doen tijdens de transferperiode");
            }

            $transfer = $this->transferRepos->find($args["id"]);
            if ($transfer === null) {
                throw new \Exception("de transfer is niet gevonden", E_ERROR);
            }

            $poolUser->getTransfers()->removeElement($transfer);
            $this->transferRepos->remove($transfer);
            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }
}
