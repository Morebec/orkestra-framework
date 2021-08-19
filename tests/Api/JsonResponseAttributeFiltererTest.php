<?php

namespace Tests\Morebec\Orkestra\Framework\Api;

use Morebec\Orkestra\Framework\Api\JsonResponseAttributeFilterer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseAttributeFiltererTest extends TestCase
{
    public function testKeepAttributesInResponsePayloadOnObject(): void
    {
        $filterer = new JsonResponseAttributeFilterer();

        // On Object
        $response = new Response(json_encode([
            'username' => 'barney.stinson',
            'emailAddress' => 'barney.stinson@goliathbank.com',
            'other' => [
                'bestFriend' => 'Ted Mosby',
                'friends' => [
                    'Marshall Eriksen',
                    'Ted Mosby',
                    'Lily Aldrin',
                    'Robin Sherbatsky',
                ],
            ],
        ], \JSON_THROW_ON_ERROR));

        $filterer->keepAttributesInResponsePayload($response, [
            'emailAddress',
            'other.bestFriend',
        ]);

        self::assertEquals([
            'emailAddress' => 'barney.stinson@goliathbank.com',
            'other' => [
                'bestFriend' => 'Ted Mosby',
            ],
        ], json_decode($response->getContent(), true));
    }

    public function testKeepAttributesInResponsePayloadOnArray(): void
    {
        $filterer = new JsonResponseAttributeFilterer();

        // On Array of Object
        // On Object
        $response = new Response(json_encode([
            [
                'id' => '123',
                'name' => 'John',
                'hobbies' => [
                    ['name' => 'golf', 'interestLevel' => 'low'],
                    ['name' => 'football', 'interestLevel' => 'medium'],
                    ['name' => 'high', 'interestLevel' => 'medium'],
                ],
            ],
            [
                'id' => '234',
                'name' => 'Jane',
                'hobbies' => [
                    ['name' => 'parkour', 'interestLevel' => 'high'],
                ],
            ],
        ], \JSON_THROW_ON_ERROR));

        $filterer->keepAttributesInResponsePayload($response, [
            '*.id',
            '*.hobbies.*.interestLevel',
        ]);

        self::assertEquals([
            [
                'id' => '123',
                'hobbies' => [
                    ['interestLevel' => 'low'],
                    ['interestLevel' => 'medium'],
                    ['interestLevel' => 'medium'],
                ],
            ],
            [
                'id' => '234',
                'hobbies' => [
                    ['interestLevel' => 'high'],
                ],
            ],
        ], json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testRemoveAttributesFromResponsePayloadOnObject(): void
    {
        $filterer = new JsonResponseAttributeFilterer();

        // On Object
        $response = new Response(json_encode([
            'username' => 'barney.stinson',
            'emailAddress' => 'barney.stinson@goliathbank.com',
            'other' => [
                'bestFriend' => 'Ted Mosby',
                'friends' => [
                    'Marshall Eriksen',
                    'Ted Mosby',
                    'Lily Aldrin',
                    'Robin Sherbatsky',
                ],
            ],
        ], \JSON_THROW_ON_ERROR));

        $filterer->removeAttributesFromResponsePayload($response, [
            'emailAddress',
            'other.bestFriend',
        ]);

        self::assertEquals([
            'username' => 'barney.stinson',
            'other' => [
                'friends' => [
                    'Marshall Eriksen',
                    'Ted Mosby',
                    'Lily Aldrin',
                    'Robin Sherbatsky',
                ],
            ],
        ], json_decode($response->getContent(), true));
    }

    public function testRemoveAttributesFromResponsePayloadOnArray(): void
    {
        $filterer = new JsonResponseAttributeFilterer();

        // On Array of Object
        // On Object
        $response = new Response(json_encode([
            [
                'id' => '123',
                'name' => 'John',
                'hobbies' => [
                    ['name' => 'golf', 'interestLevel' => 'low'],
                    ['name' => 'football', 'interestLevel' => 'medium'],
                    ['name' => 'high', 'interestLevel' => 'medium'],
                ],
            ],
            [
                'id' => '234',
                'name' => 'Jane',
                'hobbies' => [
                    ['name' => 'parkour', 'interestLevel' => 'high'],
                ],
            ],
        ], \JSON_THROW_ON_ERROR));

        $filterer->removeAttributesFromResponsePayload($response, [
            '*.id',
            '*.hobbies.*.interestLevel',
        ]);

        self::assertEquals([
            [
                'name' => 'John',
                'hobbies' => [
                    ['name' => 'golf'],
                    ['name' => 'football'],
                    ['name' => 'high'],
                ],
            ],
            [
                'name' => 'Jane',
                'hobbies' => [
                    ['name' => 'parkour'],
                ],
            ],
        ], json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }
}
