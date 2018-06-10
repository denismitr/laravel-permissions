<?php


namespace Denismitr\Permissions\Traits;


use Denismitr\Permissions\Models\AuthGroup;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait BelongsToAuthGroups
{
    use HasPermissions;

    public static function bootBelongsToAuthGroup()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->authGroups()->detach();
        });
    }


    public function joinAuthGroup(...$groups)
    {
        $groups = collect($groups)
            ->flatten()
            ->map(function ($group) {
                return $this->getAuthGroup($group);
            });

        $this->authGroups()->saveMany($groups->all());

        $this->forgetCachedPermissions();

        return $this;
    }

    public function syncAuthGroup(...$groups)
    {
        $this->authGroups()->detach();

        return $this->assignRole($groups);
    }


    public function scopeAuthGroups(Builder $query, $groups): Builder
    {
        if ($groups instanceof Collection) {
            $groups = $groups->all();
        }

        if (! is_array($groups)) {
            $groups = [$groups];
        }

        $groups = array_map(function ($group) {
            if ($group instanceof AuthGroup) {
                return $group;
            }

            return app(AuthGroup::class)->findByName($group);
        }, $groups);

        return $query->whereHas('authGroups', function ($query) use ($groups) {
            $query->where(function ($query) use ($groups) {
                foreach ($groups as $group) {
                    $query->orWhere(config('permission.table_names.auth_groups').'.id', $group->id);
                }
            });
        });
    }

    /**
     * @param $groups
     * @return bool
     */
    public function isOneOf($groups): bool
    {
        if (is_string($groups) && false !== strpos($groups, '|')) {
            $groups = $this->convertPipeToArray($groups);
        }

        if (is_string($groups)) {
            return $this->authGroups->contains('name', $groups);
        }

        if ($groups instanceof AuthGroup) {
            return $this->authGroups->contains('id', $groups->id);
        }

        if (is_array($groups)) {
            foreach ($groups as $group) {
                if ($this->isOneOf($group)) {
                    return true;
                }
            }
            return false;
        }

        return $groups->intersect($this->authGroups)->isNotEmpty();
    }

    public function isOneOfAll($groups): bool
    {
        if (is_string($groups) && false !== strpos($groups, '|')) {
            $groups = $this->convertPipeToArray($groups);
        }

        if (is_string($groups)) {
            return $this->authGroups->contains('name', $groups);
        }

        if ($groups instanceof AuthGroup) {
            return $this->authGroups->contains('id', $groups->id);
        }

        $groups = collect()->make($groups)->map(function ($group) {
            return $group instanceof AuthGroup ? $group->name : $group;
        });

        return $groups->intersect($this->authGroups->pluck('name')) == $groups;
    }

    /**
     * @return MorphToMany
     */
    public function authGroups(): BelongsToMany
    {
        return $this->belongsToMany(AuthGroup::class, 'auth_group_users');
    }

    /**
     * @return Collection
     */
    public function getDirectPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * @return Collection
     */
    public function getAuthGroupNames(): Collection
    {
        return $this->authGroups->pluck('name');
    }

    /**
     * @param $group
     * @return AuthGroup
     */
    public function getAuthGroup($group): AuthGroup
    {
        if (is_numeric($group)) {
            return app(AuthGroup::class)->findById($group);
        }

        if (is_string($group)) {
            return app(AuthGroup::class)->findByName($group, $this->getDefaultGuard());
        }

        return $group;
    }

    protected function convertPipeToArray(string $pipeString)
    {
        $pipeString = trim($pipeString);

        if (strlen($pipeString) <= 2) {
            return $pipeString;
        }

        $quoteCharacter = substr($pipeString, 0, 1);

        $endCharacter = substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (! in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }
}