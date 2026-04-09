<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BranchContext
{
    public static function user()
    {
        return Auth::user();
    }

    public static function branchId(): ?int
    {
        return self::user()?->branch_id;
    }

    public static function isPrivileged(): bool
    {
        $user = self::user();

        if (!$user) {
            return true;
        }

        return in_array($user->role, ['super_admin', 'admin'], true);
    }

    public static function scope(Builder $query, string $column = 'branch_id'): Builder
    {
        $user = self::user();

        if (!$user || self::isPrivileged()) {
            return $query;
        }

        if (!$user->branch_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($column, $user->branch_id);
    }

    public static function assign(array $data, string $column = 'branch_id'): array
    {
        $user = self::user();

        if (!$user) {
            return $data;
        }

        if (self::isPrivileged()) {
            if (!array_key_exists($column, $data) && $user->branch_id) {
                $data[$column] = $user->branch_id;
            }

            return $data;
        }

        $data[$column] = $user->branch_id;

        return $data;
    }

    public static function canAccess(?int $branchId): bool
    {
        if (self::isPrivileged()) {
            return true;
        }

        return $branchId !== null && $branchId === self::branchId();
    }

    public static function abortIfForbidden(?int $branchId): void
    {
        abort_unless(self::canAccess($branchId), 403, 'No tienes permiso para acceder a esta sucursal.');
    }
}
