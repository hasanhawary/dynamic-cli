<?php

namespace Policies;

use App\Models\Crud;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CrudPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param Crud|null $crud
     * @return bool
     */
    public function view(User $user, ?Crud $crud = null): bool
    {
        if (!($user->can('view-all-crud') || $user->can('view-own-crud'))) {
            return false;
        }

        return !$crud || $this->checkUser($user, $crud);
    }

    /**
     * @param User $user
     * @param Crud|null $crud
     * @return bool
     */
    public function create(User $user, ?Crud $crud = null): bool
    {
        if (!$user->can('create-crud')) {
            return false;
        }

        return !$crud || $this->checkUser($user, $crud);
    }

    /**
     * @param User $user
     * @param Crud $crud
     * @return bool
     */
    public function update(User $user, Crud $crud): bool
    {
        if (!$user->can('update-crud')) {
            return false;
        }

        return $this->checkUser($user, $crud);
    }

    /**
     * @param User $user
     * @param Crud $crud
     * @return bool
     */
    public function checkUser(User $user, Crud $crud): bool
    {
        return $user->can('view-all-crud') || $crud->created_by === $user->id;
    }
}
