<?php

namespace App\Traits\GoalSaving;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Goal saving history endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/goals/{id}/savings',
        summary: 'List the saving history of a goal, newest first',
        tags: ['Goals'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['amount', 'created_at'])),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'date_from', in: 'query', required: false, description: 'First day to include (Y-m-d, in the user timezone)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sub_goal_id', in: 'query', required: false, description: 'Only the savings of this sub goal', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false, description: 'Last day to include (Y-m-d, in the user timezone)', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated saving history'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/goals/{id}/savings',
        summary: 'Add an amount to the saved amount of a goal and record it in the history',
        tags: ['Goals'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 500000),
                    new OA\Property(property: 'sub_goal_id', type: 'integer', nullable: true, description: 'One of the sub goals of this goal the amount is saved for', example: null),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Saving recorded; the response holds the saving in data and the updated goal in goal'),
            new OA\Response(response: 403, description: 'The goal is already reached and can no longer be changed'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Post(
        path: '/goals/{id}/savings/generate',
        summary: 'Generate sample savings for a goal (local environment only)',
        tags: ['Goals'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'count', type: 'integer', minimum: 1, maximum: 50, example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Generated savings in data and the updated goal in goal'),
            new OA\Response(response: 403, description: 'The goal is already reached and can no longer be changed'),
            new OA\Response(response: 404, description: 'Not found, or not the local environment'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentGenerateEndpoint(): void {}

    #[OA\Put(
        path: '/goals/{id}/savings/{savingId}',
        summary: 'Change the amount of a saving; the saved amount of the goal follows',
        tags: ['Goals'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'savingId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 500000),
                    new OA\Property(property: 'sub_goal_id', type: 'integer', nullable: true, description: 'One of the sub goals of this goal the amount is saved for', example: null),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Saving updated; the response holds the saving in data and the updated goal in goal'),
            new OA\Response(response: 403, description: 'The goal is already reached and can no longer be changed'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Delete(
        path: '/goals/{id}/savings/{savingId}',
        summary: 'Delete a saving; its amount is subtracted from the saved amount of the goal',
        tags: ['Goals'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'savingId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Saving deleted; the response holds the updated goal in goal'),
            new OA\Response(response: 403, description: 'The goal is already reached and can no longer be changed'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentDestroyEndpoint(): void {}

    #[OA\Delete(
        path: '/goals/{id}/savings',
        summary: 'Bulk delete savings of a goal; their total is subtracted from the saved amount',
        tags: ['Goals'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'all', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Savings deleted; the response holds the updated goal in goal'),
            new OA\Response(response: 403, description: 'The goal is already reached and can no longer be changed'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentBulkDestroyEndpoint(): void {}
}
