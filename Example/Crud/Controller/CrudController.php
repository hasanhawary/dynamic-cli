<?php

namespace App\Http\Controllers\API\Example;

use App\Filters\Example\CrudFilter;
use App\Filters\Global\JsonDisplayNameFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Example\CrudRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\Example\CrudResource;
use App\Models\Crud;
use App\Trait\Global\HasSoftDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class CrudController extends Controller implements HasMiddleware
{
    use HasSoftDeleteMethods;

    public function __construct()
    {
        $this->setSoftDeleteModel(Crud::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-crud'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-crud'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-crud'), only: ['update']),
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Crud::query())
            ->through([CrudFilter::class, JsonDisplayNameFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, CrudResource::class));
    }

    /**
     * @param CrudRequest $request
     * @return JsonResponse
     */
    public function store(CrudRequest $request): JsonResponse
    {
        $crud = Crud::create($request->validated());

        return successResponse(new CrudResource($crud), __('api.created_success'));
    }

    /**
     * @param Crud $crud
     * @return JsonResponse
     */
    public function show(Crud $crud): JsonResponse
    {
        return successResponse(new CrudResource($crud));
    }

    /**
     * @param CrudRequest $request
     * @param Crud $crud
     * @return JsonResponse
     */
    public function update(CrudRequest $request, Crud $crud): JsonResponse
    {
        $crud->update($request->validated());

        return successResponse(new CrudResource($crud->refresh()), __('api.updated_success'));
    }
}
