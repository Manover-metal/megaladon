<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class StoreCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StoreCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Store::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/store');
        CRUD::setEntityNameStrings('Металлопрокат', 'Металлопрокат');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('user.name')->label('Пользователь');
        CRUD::column('name')->label('Наименование');
        CRUD::column('city.name')->label('Город');
        CRUD::column('bin')->label('БИН');
        CRUD::column('full_address')->label('Адрес');
        CRUD::column('rating')->label('Рейтинг');

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(StoreRequest::class);

        CRUD::addField([
            'label'     => 'Пользователь', // Table column heading
            'type'      => 'select',
            'name'      => 'user_id', // the column that contains the ID of that connected entity;
            'entity'    => 'user', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\User",
        ]);
        CRUD::addField([
            'label'     => 'Город', // Table column heading
            'type'      => 'select',
            'name'      => 'city_id', // the column that contains the ID of that connected entity;
            'entity'    => 'city', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\City",
        ]);
        CRUD::field('name')->label('Наименование');
        CRUD::field('bin')->label('БИН');
        CRUD::field('full_address')->label('Адрес');
        CRUD::field('lat');
        CRUD::field('lon');
        CRUD::field('rating')->label('Рейтинг');

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function autoSetupShowOperation()
    {
        $this->setupListOperation();

        CRUD::addColumn([
            'name'  => 'contacts',
            'label' => 'Контакты', // Table column heading
            'type'  => 'model_function',
            'function_name' => 'getContacts',
            'escaped' => false,
            'limit' => 10000,
        ]);
        
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'Создан',
        ]);
        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => 'Обновлён',
        ]);
    }
}
