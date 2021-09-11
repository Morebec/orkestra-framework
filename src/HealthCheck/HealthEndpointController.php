<?php

namespace Morebec\Orkestra\Framework\HealthCheck;

use Morebec\Orkestra\Framework\Api\JsonResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthEndpointController extends AbstractController
{
    private HealthCheckerRunnerInterface $healthCheckerRunner;
    private JsonResponseFactory $jsonResponseFactory;

    public function __construct(HealthCheckerRunnerInterface $healthCheckerRunner, JsonResponseFactory $jsonResponseFactory)
    {
        $this->healthCheckerRunner = $healthCheckerRunner;
        $this->jsonResponseFactory = $jsonResponseFactory;
    }

    /**
     * @Route(name="api.health", path="/_internal_/health")
     */
    public function __invoke(): JsonResponse
    {
        $result = $this->healthCheckerRunner->run();

        return $this->jsonResponseFactory->makeSuccessResponse($result->toArray());
    }
}
