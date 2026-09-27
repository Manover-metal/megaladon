<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/** Флаги приложения. Строки заводятся миграциями — создавать и удалять нельзя. */
class SettingCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    public function setup()
    {
        CRUD::setModel(Setting::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/setting');
        CRUD::setEntityNameStrings('настройка', 'настройки');
    }

    protected function setupListOperation()
    {
        CRUD::column('label')->label('Настройка');
        CRUD::addColumn(['name' => 'value', 'label' => 'Включено', 'type' => 'boolean']);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation(['value' => 'required|in:0,1']);
        CRUD::addField(['name' => 'value', 'label' => 'Включено', 'type' => 'checkbox']);
    }
}
