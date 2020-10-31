<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use App\Response\ForbiddenResponse as ForbiddenResponse;
use Selective\Config\Configuration;
use Slim\Factory\ServerRequestCreatorFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;

final class PoolAction extends Action
{
    /**
     * @var PoolRepository
     */
    private $poolRepos;
    /**
     * @var Configuration
     */
    protected $config;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        PoolRepository $poolRepos,
        Configuration $config
    ) {
        parent::__construct($logger, $serializer);
        $this->poolRepos = $poolRepos;
        $this->config = $config;
    }

//    public function fetch(Request $request, Response $response, $args): Response
//    {
//        try {
//            /** @var BetGameFilter $betGameFilter */
//            $betGameFilter = $this->serializer->deserialize($this->getRawData(), 'SuperElf\BetGameFilter', 'json');
//
//            $betGames = $this->betGameRepos->findByExt( $betGameFilter );
//
//            $json = $this->serializer->serialize($betGames, 'json');
//            return $this->respondWithJson($response, $json);
//        } catch (\Exception $e) {
//            return new ErrorResponse($e->getMessage(), 400);
//        }
//    }
}
