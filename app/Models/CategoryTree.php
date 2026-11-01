<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;

/**
 * Дерево категорий любой глубины на parent_id (корни — 0 или NULL).
 * Модель задаёт колонку названия в константе LABEL.
 */
trait CategoryTree
{
    private static ?array $pathCache = null;

    public function child()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /** Корни с проставленной на всю глубину связью child — одним запросом. */
    public static function tree(): Collection
    {
        $all = static::orderBy('id')->get();
        $byParent = $all->groupBy(fn ($c) => (int) $c->parent_id);
        foreach ($all as $c) {
            $c->setRelation('child', $byParent[$c->id] ?? new Collection());
        }

        return $byParent[0] ?? new Collection();
    }

    /** Все категории в порядке обхода дерева: [[модель, глубина], …]. */
    public static function flatTree(): array
    {
        $byParent = static::orderBy('id')->get()->groupBy(fn ($c) => (int) $c->parent_id);
        $out = [];
        $walk = function (int $parent, int $depth) use (&$walk, &$out, $byParent) {
            foreach ($byParent[$parent] ?? [] as $c) {
                $out[] = [$c, $depth];
                $walk($c->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }

    /** id категории вместе со всеми потомками — для фильтров по ветке. */
    public static function descendantIds(int $id): array
    {
        $ids = [$id];
        $level = [$id];
        while ($level = static::whereIn('parent_id', $level)->whereNotIn('id', $ids)->pluck('id')->all()) {
            $ids = [...$ids, ...$level];
        }

        return $ids;
    }

    public static function isLeaf(int $id): bool
    {
        return static::whereKey($id)->exists() && !static::where('parent_id', $id)->exists();
    }

    /**
     * Заливка дерева из сидера: ['Родитель' => ['Ребёнок', 'Ветка' => [...]]].
     * Повторный запуск не плодит дубли — ищем по названию внутри родителя.
     */
    public static function seedTree(array $nodes, int $parentId = 0): void
    {
        foreach ($nodes as $key => $value) {
            $name = is_array($value) ? $key : $value;
            $c = static::firstOrCreate([static::LABEL => $name, 'parent_id' => $parentId]);
            if (is_array($value)) {
                static::seedTree($value, $c->id);
            }
        }
    }

    /** Полный путь «Резка металла › Лазерная резка» — подписи в админке. */
    public static function path(int $id): ?string
    {
        if (self::$pathCache === null) {
            self::$pathCache = [];
            $stack = [];
            foreach (static::flatTree() as [$c, $depth]) {
                $stack = [...array_slice($stack, 0, $depth), $c->{static::LABEL}];
                self::$pathCache[$c->id] = implode(' › ', $stack);
            }
        }

        return self::$pathCache[$id] ?? null;
    }
}
