<?php

namespace App\Traits\SubGoal;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Sub goal endpoints (the steps needed to reach a goal).
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/goals/{id}/sub-goals',
        summary: 'List the sub goals of a goal (they are created and edited through the goal endpoints)',
        tags: ['Sub Goals'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['name', 'target_amount', 'saved_amount', 'desired_date', 'is_done', 'note', 'created_at'])),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated sub goals in data, plus summary (total, done, target_total, saved_total of all the goal sub goals)'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Put(
        path: '/goals/{id}/sub-goals',
        summary: 'Replace the sub goals of a goal',
        description: 'Items with an id are updated (their saved amount is kept, it follows the savings), items without one are created and the sub goals left out are removed (not allowed for a sub goal that has savings). The target amounts must add up to the goal target amount and the saved amounts to the goal saved amount; a desired_date cannot be later than the goal desired date.',
        tags: ['Sub Goals'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'sub_goals',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer', nullable: true),
                            new OA\Property(property: 'name', type: 'string', example: 'Mas kawin'),
                            new OA\Property(property: 'target_amount', type: 'number', format: 'float', example: 5000000),
                            new OA\Property(property: 'saved_amount', type: 'number', format: 'float', nullable: true, example: 0),
                            new OA\Property(property: 'desired_date', type: 'string', format: 'date', nullable: true),
                            new OA\Property(property: 'note', type: 'string', nullable: true),
                        ])
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'The sub goals in data, plus summary'),
            new OA\Response(response: 403, description: 'The goal is already reached and can no longer be changed'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentSyncEndpoint(): void {}

    #[OA\Get(
        path: '/goals/{id}/sub-goals/{subGoalId}',
        summary: 'Show a sub goal',
        tags: ['Sub Goals'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'subGoalId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'The sub goal'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/goals/{id}/sub-goals/{subGoalId}',
        summary: 'Update a sub goal (name, desired date, note, done flag; its amounts change through the goal sub goals)',
        tags: ['Sub Goals'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'subGoalId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Mas kawin'),
                    new OA\Property(property: 'desired_date', type: 'string', format: 'date', nullable: true, description: 'Not later than the goal desired date'),
                    new OA\Property(property: 'note', type: 'string', nullable: true),
                    new OA\Property(property: 'is_done', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Sub goal updated'),
            new OA\Response(response: 403, description: 'The goal is already reached and can no longer be changed'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}
}
