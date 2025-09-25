<?php

namespace App\Trait\Global;

use App\Http\Controllers\API\DataEntry\CountryController;
use App\Http\Controllers\API\Example\CrudController;
use App\Http\Requests\Global\Other\ModelBatchRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Spatie\Permission\Exceptions\UnauthorizedException;

trait HasSoftDeleteMethods
{
    protected string $model;
    protected array $validations = [];

    /**
     * Set the model class to use.
     *
     * @param string $model
     * @return void
     */
    public function setSoftDeleteModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * Set custom validations for deleting a model.
     *
     * This method allows you to define a set of callback functions to determine whether a model
     * can be deleted. Each callback receives the model instance as its parameter and should return
     * a boolean value, where true means the model can be deleted, and false otherwise.
     *
     * Example usage:
     * $this->setDeleteValidations([
     *     // The callback checks if the model's 'can_delete' property permits deletion.
     *     // If 'can_delete' is null, deletion is allowed (defaults to true).
     *     fn($model) => $model->can_delete ?? true,
     * ]);
     *
     * Use this method to incorporate custom business logic prior to deletion.
     *
     * @param array $validations An array of callback functions to validate delete conditions.
     * @return void
     */
    public function setDeleteValidations(array $validations = []): void
    {
        $this->validations = $validations;
    }

    /**
     * @param string $action
     * @return void
     * @throws UnauthorizedException
     */
    private function checkPolicy(string $action): void
    {
        $user = auth()->user();
        $policy = "$action-" . getModelKey($this->model);

        if (!$user) {
            return;
        }

        if (isRoot()) {
            return;
        }

        if (!$user->can($policy)) {
            throw new UnauthorizedException(403);
        }
    }

    /**
     * @param int|array $ids
     * @return bool
     */
    private function canDelete(int|array $ids): bool
    {
        $objects = $this->model::find(Arr::wrap($ids));

        foreach ($objects as $object) {
            foreach ($this->validations as $validation) {
                if (is_callable($validation) && !$validation($object)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param ModelBatchRequest $request
     * @return JsonResponse
     */
    public function destroy(ModelBatchRequest $request): JsonResponse
    {
        $this->checkPolicy('delete');

        if (!$this->canDelete($request->input('ids'))) {
            return failResponse(msg: resolveTrans('not_allowed_to_delete', 'validation'));
        }

        $this->model::whereIn('id', Arr::wrap($request->input('ids')))->delete();

        return successResponse(msg: resolveTrans('deleted_success'));
    }

    /**
     * @param ModelBatchRequest $request
     * @return JsonResponse
     */
    public function restore(ModelBatchRequest $request): JsonResponse
    {
        $this->checkPolicy('restore');

        $this->model::onlyTrashed()->whereIn('id', $request->input('ids'))->restore();

        return successResponse(msg: resolveTrans('restored_success'));
    }

    /**
     * @param ModelBatchRequest $request
     * @return JsonResponse
     */
    public function forceDelete(ModelBatchRequest $request): JsonResponse
    {
        $this->checkPolicy('force-delete');

        if (!$this->canDelete($request->input('ids'))) {
            return failResponse(msg: resolveTrans('not_allowed_to_delete', 'validation'));
        }

        $this->model::onlyTrashed()->whereIn('id', $request->input('ids'))->forceDelete();

        return successResponse(msg: resolveTrans('deleted_success'));
    }
}
