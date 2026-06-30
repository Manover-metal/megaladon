<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ExecutorRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExecutorCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExecutorCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Executor::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/executor');
        CRUD::setEntityNameStrings('исполнитель', 'исполнители');
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
        CRUD::addColumn([
            'label'     => 'Город', // Table column heading
            'type'      => 'select',
            'name'      => 'city_id', // the column that contains the ID of that connected entity;
            'entity'    => 'city', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\City", // foreign key model
        ]);
        CRUD::column('name')->label('Имя');
        CRUD::column('description')->label('Описание')->limit(20);
        CRUD::column('bin')->label('БИН');
        CRUD::column('full_address')->label('Адрес');
        CRUD::column('rating')->label('Рейтинг');
        CRUD::column('lat');
        CRUD::column('lon');

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
        CRUD::setValidation(ExecutorRequest::class);

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
            'model'     => "App\Models\City", // foreign key model
        ]);
        CRUD::field('name')->label('Имя');
        CRUD::addField([
            'label' => 'Описание',
            'name' => 'description',
            'type' => 'textarea'
        ]);
        CRUD::addField([
            // n-n relationship (with pivot table)
            'label'     => 'Услуги', // Table column heading
            'type'      => 'select_multiple',
            'name'      => 'services', // the method that defines the relationship in your Model
            'entity'    => 'services', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => 'App\Models\ServiceType', // foreign key model
        ]);
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
        CRUD::column('user.name')->label('Пользователь');
        CRUD::addColumn([
            'label'     => 'Город', // Table column heading
            'type'      => 'select',
            'name'      => 'city_id', // the column that contains the ID of that connected entity;
            'entity'    => 'city', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\City", // foreign key model
        ]);
        CRUD::column('name')->label('Имя');
        CRUD::column('bin')->label('БИН');
        CRUD::addColumn([
            // n-n relationship (with pivot table)
            'label'     => 'Услуги', // Table column heading
            'type'      => 'select_multiple',
            'name'      => 'services', // the method that defines the relationship in your Model
            'entity'    => 'services', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => 'App\Models\ServiceType', // foreign key model
        ]);
        CRUD::column('full_address')->label('Адрес');
        CRUD::column('rating')->label('Рейтинг');
        CRUD::column('lat');
        CRUD::column('lon');
        CRUD::addColumn([
            'label' => 'Описание',
            'name' => 'description',
            'limit' => 3000
        ]);
    }
}
