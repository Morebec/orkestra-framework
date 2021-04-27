<?php


namespace Morebec\Orkestra\OrkestraFramework\Framework\Web\Api;


use Morebec\Orkestra\Messaging\MessageHeaders;
use Morebec\Orkestra\OrkestraServer\Api\v1\InvalidApiRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CommandController extends AbstractApiController
{
    /**
     * @Route(
     *     name="api.command.execute",
     *     path="/command/{commandTypeName}",
     *     methods={"POST"},
     *     priority="0"
     * )
     *
     * @throws InvalidApiRequestException
     */
    public function __invoke(Request $request, string $commandTypeName): JsonResponse
    {
        try {
            $command = $this->messageNormalizer->denormalize($request->request->all(), $commandTypeName);
        } catch (\Throwable $throwable) {
            return JsonApiResponseBuilder::createFailure(
                (new \ReflectionClass($throwable))->getShortName(),
                $throwable->getMessage(),
            );
        }
        $response = $this->messageBus->sendMessage($command, new MessageHeaders([
            MessageHeaders::APPLICATION_ID => 'api'
        ]));

        return $this->createResponse($command, $response);
    }
}