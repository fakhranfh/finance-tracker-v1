<?php

namespace App\Traits\Goal;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Goal endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/goals',
        summary: 'List goals',
        tags: ['Goals'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated list of goals')]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/goals',
        summary: 'Create a goal',
        tags: ['Goals'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Laptop baru'),
                    new OA\Property(property: 'target_amount', type: 'number', format: 'float', example: 15000000),
                    new OA\Property(property: 'saved_amount', type: 'number', format: 'float', example: 15000000),
                    new OA\Property(property: 'desired_date', type: 'string', format: 'date', example: '2026-12-31'),
                    new OA\Property(property: 'color', type: 'string', example: '#2563EB'),
                    new OA\Property(property: 'icon', type: 'string', example: 'flag'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'paused', 'reached'], example: 'active'),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Dana liburan akhir tahun'),
                    new OA\Property(
                        property: 'sub_goals',
                        type: 'array',
                        description: 'Optional. The steps needed to reach the goal. Their target_amount must add up to the goal target_amount and their saved amounts to the goal saved_amount. On update the list replaces the current sub goals: items with an id are kept, items without one are created, the rest are removed (not allowed for a sub goal that has savings); leave it out to keep them as they are.',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer', nullable: true, description: 'Update only: an existing sub goal of this goal; its saved amount is not changed here'),
                            new OA\Property(property: 'name', type: 'string', example: 'Mas kawin'),
                            new OA\Property(property: 'target_amount', type: 'number', format: 'float', example: 5000000),
                            new OA\Property(property: 'saved_amount', type: 'number', format: 'float', nullable: true, example: 0),
                            new OA\Property(property: 'desired_date', type: 'string', format: 'date', nullable: true, description: 'Not later than the goal desired_date'),
                            new OA\Property(property: 'note', type: 'string', nullable: true),
                        ])
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Goal created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Get(
        path: '/goals/{id}',
        summary: 'Show a goal',
        tags: ['Goals'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The goal'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/goals/{id}',
        summary: 'Update a goal',
        tags: ['Goals'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Laptop baru'),
                    new OA\Property(property: 'target_amount', type: 'number', format: 'float', example: 15000000),
                    new OA\Property(property: 'saved_amount', type: 'number', format: 'float', example: 15000000),
                    new OA\Property(property: 'desired_date', type: 'string', format: 'date', example: '2026-12-31'),
                    new OA\Property(property: 'color', type: 'string', example: '#2563EB'),
                    new OA\Property(property: 'icon', type: 'string', example: 'flag'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'paused', 'reached'], example: 'active'),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Dana liburan akhir tahun'),
                    new OA\Property(
                        property: 'sub_goals',
                        type: 'array',
                        description: 'Optional. The steps needed to reach the goal. Their target_amount must add up to the goal target_amount and their saved amounts to the goal saved_amount. On update the list replaces the current sub goals: items with an id are kept, items without one are created, the rest are removed (not allowed for a sub goal that has savings); leave it out to keep them as they are.',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer', nullable: true, description: 'Update only: an existing sub goal of this goal; its saved amount is not changed here'),
                            new OA\Property(property: 'name', type: 'string', example: 'Mas kawin'),
                            new OA\Property(property: 'target_amount', type: 'number', format: 'float', example: 5000000),
                            new OA\Property(property: 'saved_amount', type: 'number', format: 'float', nullable: true, example: 0),
                            new OA\Property(property: 'desired_date', type: 'string', format: 'date', nullable: true, description: 'Not later than the goal desired_date'),
                            new OA\Property(property: 'note', type: 'string', nullable: true),
                        ])
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Goal updated'),
            new OA\Response(response: 403, description: 'The goal is already reached and can no longer be changed'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Delete(
        path: '/goals/{id}',
        summary: 'Delete a goal',
        tags: ['Goals'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Goal deleted')]
    )]
    private function documentDestroyEndpoint(): void {}

    #[OA\Delete(
        path: '/goals',
        summary: 'Bulk delete goals',
        tags: ['Goals'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'all', type: 'boolean'),
                    new OA\Property(property: 'search', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 204, description: 'Goals deleted')]
    )]
    private function documentBulkDestroyEndpoint(): void {}
}
