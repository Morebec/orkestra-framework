<?php

namespace Morebec\Orkestra\Framework\Api;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service capable of changing the returned attributes in the data of a JsonResponse (as constructed by {@link JsonResponseFactory}).
 * This can be useful to hide/show certain attributes depending on the role/permissions of a user or any other type of logic.
 * It uses dot notation in order to define filters.
 * Wildcard * is also accepted for arrays of objects.
 *
 * There are two operations:
 * - removing attributes: removes specified attributes according to paths
 * - keeping attributes: keeps specified attributes according to paths
 * E.g.:
 * "project.title"
 * "project.tasks.*.name
 *  "*.nested*.value"
 */
class JsonResponseAttributeFilterer
{
    /**
     * Removes some attributes from a response.
     *
     * @param string[] $attributes list of attributes. It supports dot notation.
     */
    public function removeAttributesFromResponsePayload(Response $response, array $attributes): void
    {
        $payload = $this->decodeResponseContent($response);

        foreach ($attributes as $attribute) {
            $this->unsetArrayKeyUsingDotNotation($payload, $attribute);
        }
        $response->setContent(json_encode($payload, \JSON_THROW_ON_ERROR));
    }

    /**
     * Allows to restrict the shown attributes to a given list.
     *
     * @param array $attributes list of attributes. It supports dot notation.
     */
    public function keepAttributesInResponsePayload(Response $response, array $attributes): void
    {
        $initialPayload = $this->decodeResponseContent($response);

        // copy array
        $shadowPayload = $initialPayload;

        foreach ($attributes as $attribute) {
            $this->unsetArrayKeyUsingDotNotation($shadowPayload, $attribute);
        }

        $payload = $this->compareArrays($initialPayload, $shadowPayload);

        $response->setContent(json_encode($payload, \JSON_THROW_ON_ERROR));
    }

    /**
     * @throws JsonException
     */
    protected function decodeResponseContent(Response $response): array
    {
        return json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    private function unsetArrayKeyUsingDotNotation(array &$array, string $path): void
    {
        // The algorithm is basically to use the explosion of the dotted path as a depth iterator.
        // We will go down this iterator until we actually find the value (if it exists).
        // At which point we will be able to unset the value.
        // However, we also want to be able to support wildcards * for array keys, meaning we will need to go down and check any element.
        // This algorithm uses pointers in order not to copy arrays unnecessarily.
        $pathKeys = explode('.', $path);

        /**
         * Pointer to the array at the depth level we are for a given pathKey iteration.
         */
        $currentArrayPointer = &$array;

        /**
         * Parent of the currentArrayPointer, so we can do unset(parent[key]) once we have the final key.
         */
        $parentArrayPointer = &$array;

        // Represents the key we need to delete inside the parentArrayPointer.
        $targetKey = null;

        foreach ($pathKeys as $index => $pathKey) {
            // If the key does not exist in the current array
            // we can skip the function entirely, no need to go nested.
            // There is one case, however, where the key might not exist as is, but as a wildcard. we will check this first.

            $isWildCard = $pathKey === '*';
            $hasKey = \array_key_exists($pathKey, $currentArrayPointer);

            // The key does not exist, and there is no wildcard, we can abort.
            // Or throw an exception (?).
            if (!$hasKey && !$isWildCard) {
                return;
            }

            if ($isWildCard) {
                // This means that the key regardless of what it is, should be considered.
                // We will run it for everything.
                foreach (array_keys($currentArrayPointer) as $k) {
                    $remainingPathKeys = \array_slice($pathKeys, $index + 1, \count($pathKeys) - $index - 1, true);
                    $this->unsetArrayKeyUsingDotNotation(
                        $currentArrayPointer[$k],
                        implode('.', $remainingPathKeys)
                    );
                }

                return;
            }

            // If not the first part of the path (first key as exploded), it means we do have a parent.
            // Set the parent pointer to be the current one we are going a level deeper.
            if ($index !== 0) {
                $parentArrayPointer = &$currentArrayPointer;
            }

            // The current pointer is now the nested one.
            $targetKey = $pathKey;
            $currentArrayPointer = &$currentArrayPointer[$pathKey];
        }

        // At this point we have found the targetKey in the parent array pointer.
        if ($targetKey) {
            unset($parentArrayPointer[$targetKey]);
        }
    }

    /**
     * Compares two multidimensional arrays to return the difference, that is things that are in A that are not in B
     * or different for a given key in B.
     */
    private function compareArrays(array $arrayA, array $arrayB): array
    {
        $result = [];

        foreach ($arrayA as $key => $value) {
            // If key does not exist we have a difference.
            if (!\array_key_exists($key, $arrayB)) {
                $result[$key] = $value;
                continue;
            }

            // If the value is an array
            if (\is_array($value)) {
                $recursiveDiff = $this->compareArrays($value, $arrayB[$key]);
                if ($recursiveDiff) {
                    $result[$key] = $recursiveDiff;
                }
                continue;
            }

            if ($value !== $arrayB[$key]) {
                $result[$key] = $value;
            }
            continue;
        }

        return $result;
    }
}
