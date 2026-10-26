<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    /**
     * Типы объектов, к которым может относиться отзыв.
     * Ключ — класс модели ровно в том виде, в каком он лежит
     * в ratingable_type (morph map в проекте не настроен),
     * значение — подпись для админки.
     */
    public const RATINGABLE_TYPES = [
        Executor::class => 'Исполнитель',
        Store::class => 'Магазин',
    ];

    protected $fillable = [
        'user_id',
        'rate',
        'comment',
        // Виртуальное поле формы админки: связь ratingable полиморфная.
        // Должно быть в fillable, иначе fill() не вызовет мутатор.
        'ratingable_key',
    ];

    public function ratingable()
    {
        return $this->morphTo('ratingable');
    }

    public function media()
    {
        return $this->morphMany(MediaFiles::class, 'mediable');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Подпись объекта отзыва для списка и карточки в админке.
     * Исполнителя или магазин могли удалить, а отзыв остался —
     * поэтому отдаём прочерк, а не падаем на null.
     */
    public function getRatingableLabelAttribute(): string
    {
        $target = $this->ratingable;

        if (is_null($target)) {
            return '— (удалён)';
        }

        $typeLabel = self::RATINGABLE_TYPES[$this->ratingable_type] ?? 'Объект';
        $name = $target->name ?: ('#' . $target->getKey());

        return $typeLabel . ' — ' . $name;
    }

    /**
     * Значение для поля «Объект отзыва» в форме редактирования:
     * класс и идентификатор, склеенные через «|».
     */
    public function getRatingableKeyAttribute(): ?string
    {
        if (is_null($this->ratingable_type) || is_null($this->ratingable_id)) {
            return null;
        }

        return $this->ratingable_type . '|' . $this->ratingable_id;
    }

    /**
     * Обратная операция: раскладываем ключ из формы в реальные колонки.
     * Формат и существование объекта проверяет RatingRequest, здесь
     * только защищаемся от мусора, чтобы не писать в базу битую связь.
     */
    public function setRatingableKeyAttribute(?string $value): void
    {
        if (!is_string($value) || !str_contains($value, '|')) {
            return;
        }

        [$type, $id] = explode('|', $value, 2);

        if (!array_key_exists($type, self::RATINGABLE_TYPES) || !ctype_digit($id)) {
            return;
        }

        $this->attributes['ratingable_type'] = $type;
        $this->attributes['ratingable_id'] = (int) $id;
    }
}
