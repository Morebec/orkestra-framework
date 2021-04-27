<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\Web\Api;

use Morebec\Orkestra\Messaging\MessageHeaders;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class QueryController extends AbstractApiController
{
    /**
     * @Route(
     *     name="api.query.execute",
     *     path="/query/{queryTypeName}",
     *     methods={"POST"}
     * )
     *
     * @throws InvalidApiRequestException
     */
    public function __invoke(Request $request, string $queryTypeName): JsonResponse
    {
        // TODO Authenticate call.
        try {
            $query = $this->messageNormalizer->denormalize($request->request->all(), $queryTypeName);
        } catch (\Throwable $throwable) {
            return JsonApiResponseBuilder::createFailure(
                (new \ReflectionClass($throwable))->getShortName(),
                $throwable->getMessage(),
            );
        }
        $response = $this->messageBus->sendMessage($query, new MessageHeaders([
            MessageHeaders::APPLICATION_ID => 'api'
        ]));

        return $this->createResponse($query, $response);
    }
}
