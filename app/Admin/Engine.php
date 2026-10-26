<?php

namespace App\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Универсальный CRUD админки поверх конфигов из Resources.php: список с
 * поиском, фильтрами и сортировкой, карточка, сохранение через Eloquent
 * (срабатывают мутаторы и observer'ы моделей), удаление, кнопки строки.
 * Next.js-админка (megaladon_admin) только рисует то, что отдаёт этот класс.
 */
class Engine
{
    public const PAGE_SIZE = 25;

    public function __construct(private array $r)
    {
    }

    public static function for(string $key): ?self
    {
        $r = Resources::get($key);

        return $r ? new self($r) : null;
    }

    public function allows(string $op): bool
    {
        return $this->r[$op] ?? true;
    }

    private function query(): Builder
    {
        return ($this->r['model'])::query();
    }

    // Для подписей и карточки берём и удалённых (soft delete): жалоба на
    // удалённого пользователя должна показывать его имя, а не «#id».
    private static function withTrashed(string $model): Builder
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model)) ? $model::withTrashed() : $model::query();
    }

    private function find(int $id): ?Model
    {
        return self::withTrashed($this->r['model'])->find($id);
    }

    // Имена колонок приходят из URL: только [a-z0-9_], остальное — 400.
    private static function ident(string $s): string
    {
        if (!preg_match('/^[a-z0-9_]+$/', $s)) {
            abort(400, "Недопустимое имя колонки: $s");
        }

        return $s;
    }

    // Сырые значения из БД (даты строками 'Y-m-d H:i:s', json строкой),
    // без hidden-полей (пароль). Так же, как админка читала их напрямую.
    private static function row(Model $m): array
    {
        return Arr::except($m->getAttributes(), $m->getHidden());
    }

    public function list(array $in): array
    {
        $page = max(1, (int) ($in['page'] ?? 1));
        $q = $this->query();

        foreach ($this->r['filters'] ?? [] as $name => $f) {
            if ($f === 'daterange') {
                if ($v = $in["f_{$name}_from"] ?? null) {
                    $q->where($name, '>=', "$v 00:00:00");
                }
                if ($v = $in["f_{$name}_to"] ?? null) {
                    $q->where($name, '<=', "$v 23:59:59");
                }
                continue;
            }
            $v = $in["f_$name"] ?? null;
            if ($v === null || $v === '') {
                continue;
            }
            if ($f instanceof \Closure) {
                $f($q, (string) $v);
            } elseif ($f === 'boolean') {
                $q->where($name, $v === 'yes' ? 1 : 0);
            } else {
                $q->where($name, $v);
            }
        }

        // Связанные записи под карточкой: where[order_id]=5.
        foreach ((array) ($in['where'] ?? []) as $k => $v) {
            $q->where(self::ident($k), $v);
        }

        $s = trim((string) ($in['q'] ?? ''));
        if ($s !== '' && !empty($this->r['search'])) {
            $q->where(function ($w) use ($s) {
                foreach ($this->r['search'] as $c) {
                    $w->orWhere($c, 'like', "%$s%");
                }
            });
        }

        $total = (clone $q)->count();

        [$sortCol, $sortDir] = $this->r['defaultSort'] ?? ['id', 'desc'];
        if (!empty($in['sort'])) {
            $sortCol = self::ident($in['sort']);
            $sortDir = ($in['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        }

        $models = $q->orderBy($sortCol, $sortDir)->orderBy('id', 'desc')
            ->forPage($page, self::PAGE_SIZE)->get();
        $rows = $models->map(fn (Model $m) => self::row($m))->all();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PAGE_SIZE)),
            'labels' => $this->labels($rows),
            'extra' => isset($this->r['listExtra']) ? $this->r['listExtra']($models) : [],
            'sortCol' => $sortCol,
            'sortDir' => $sortDir,
        ];
    }

    public function get(int $id): ?array
    {
        $m = $this->find($id);
        if (!$m) {
            return null;
        }
        $row = self::row($m);

        return [
            'row' => $row,
            'labels' => $this->labels([$row]),
            'extra' => array_merge(
                isset($this->r['listExtra']) ? $this->r['listExtra'](new Collection([$m])) : [],
                isset($this->r['extra']) ? $this->r['extra']($m) : [],
            ),
            'formValues' => isset($this->r['formValues']) ? $this->r['formValues']($m) : [],
        ];
    }

    private static function label(array $rel, Model $m): string
    {
        $label = $rel[1];

        return (string) ($label instanceof \Closure ? $label($m) : $m->getAttribute($label));
    }

    // Подписи relation-колонок: на каждую связь один запрос WHERE id IN (...).
    private function labels(array $rows): array
    {
        $out = [];
        foreach ($this->r['relations'] ?? [] as $name => $rel) {
            $ids = collect($rows)->pluck($name)->filter(fn ($v) => $v !== null && $v !== '' && (int) $v !== 0)->unique()->values();
            if ($ids->isEmpty()) {
                continue;
            }
            $out[$name] = [];
            foreach (self::withTrashed($rel[0])->whereIn('id', $ids)->get() as $m) {
                $out[$name][(string) $m->id] = self::label($rel, $m);
            }
        }

        return $out;
    }

    // Варианты для select-ов фильтров и формы: по связям и по закрытым
    // спискам (fieldOptions). Удалённые (soft delete) не предлагаем.
    public function options(): array
    {
        $relations = [];
        foreach ($this->r['relations'] ?? [] as $name => $rel) {
            $q = ($rel[0])::query();
            foreach ($rel[2] ?? [] as $k => $v) {
                $q->where($k, $v);
            }
            $q->orderBy(is_string($rel[1]) ? $rel[1] : 'id')->limit(2000);
            $relations[$name] = [];
            foreach ($q->get() as $m) {
                $relations[$name][(string) $m->id] = self::label($rel, $m) ?: "#$m->id";
            }
        }
        $fields = [];
        foreach ($this->r['fieldOptions'] ?? [] as $name => $fn) {
            $fields[$name] = $fn();
        }

        return ['relations' => $relations, 'fields' => $fields];
    }

    /**
     * Создание/изменение. Поля — из конфига ('text!', 'relation', …), лишнее
     * из формы игнорируется. Пишем через forceFill: список колонок уже
     * ограничен конфигом, а мутаторы (пароль, фото, ratingable_key) и
     * observer'ы (OrderObserver) срабатывают как при обычном save().
     */
    public function save(?int $id, Request $req): int
    {
        $model = $this->r['model'];
        $m = $id ? $this->find($id) : new $model();
        if (!$m) {
            abort(404);
        }

        $data = [];
        $raw = [];
        foreach ($this->r['fields'] ?? [] as $name => $spec) {
            $required = str_ends_with($spec, '!');
            $type = rtrim($spec, '!');

            if ($type === 'readonly') {
                continue;
            }
            if ($type === 'multiselect') {
                $raw[$name] = array_map('intval', (array) $req->input($name, []));
                continue;
            }
            if ($type === 'image') {
                if ($req->hasFile($name)) {
                    $data[$name] = $req->file($name);
                }
                continue;
            }
            if ($type === 'boolean') {
                $data[$name] = $req->boolean($name) ? 1 : 0;
                continue;
            }

            $v = $req->input($name);
            $v = is_string($v) ? trim($v) : $v;
            $empty = $v === null || $v === '';

            if ($type === 'password') {
                // Только при создании и при явной смене; пустое при изменении — не трогаем.
                if (!$empty) {
                    $data[$name] = $v;
                } elseif ($required && !$id) {
                    throw new AdminError("Не заполнено обязательное поле: $name");
                }
                continue;
            }
            if ($empty) {
                if ($required) {
                    throw new AdminError("Не заполнено обязательное поле: $name");
                }
                $data[$name] = null;
                continue;
            }
            if (in_array($type, ['number', 'relation'])) {
                if (!is_numeric($v)) {
                    throw new AdminError("Поле $name должно быть числом");
                }
                $v = $v + 0;
            }
            $data[$name] = $v;
        }

        if (isset($this->r['beforeSave'])) {
            $this->r['beforeSave']($m, $data, $raw);
        }
        $m->forceFill($data)->save();
        if (isset($this->r['afterSave'])) {
            $this->r['afterSave']($m, $raw);
        }

        return $m->id;
    }

    public function remove(int $id): void
    {
        $this->find($id)?->delete();
    }

    /** Кнопка строки (бан, решение жалобы). Возвращает текст ошибки или null. */
    public function action(string $key, int $id, string $input, int $adminId): ?string
    {
        $m = $this->find($id);
        if (!$m) {
            return 'Запись не найдена';
        }
        if (!isset($this->r['action'])) {
            return 'Действие недоступно';
        }

        return $this->r['action']($key, $m, $input, $adminId);
    }
}
