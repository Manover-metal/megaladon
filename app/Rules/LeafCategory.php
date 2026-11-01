<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/** Категория существует и у неё нет вложенных: выбирать можно только конечную. */
class LeafCategory implements Rule
{
    /** @param class-string $model OrderCategory или AdCategory */
    public function __construct(private string $model)
    {
    }

    public function passes($attribute, $value)
    {
        return is_numeric($value) && ($this->model)::isLeaf((int) $value);
    }

    public function message()
    {
        return __('validation.leaf_category');
    }
}
