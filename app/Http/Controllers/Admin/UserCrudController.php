<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('user', 'users');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'photo_url',
            'label' => 'Фото',
            'type' => 'image',
        ]);
        CRUD::addColumn([
            'name' => 'name',
            'label' => 'Имя',
            'type' => 'text',
        ]);
        CRUD::addColumn([
            'name' => 'phone',
            'label' => 'Телефон',
            'type' => 'text',
        ]);
        CRUD::addColumn([
            'name' => 'email',
            'label' => 'Email',
            'type' => 'text',
        ]);
        CRUD::addColumn([
            'name' => 'is_phone_confirmed',
            'label' => 'Подтверждён',
            'type' => 'boolean',
        ]);
        CRUD::addColumn([
            'label'     => 'Город',
            'type'      => 'select',
            'name'      => 'city_id',
            'entity'    => 'city',
            'attribute' => 'name',
            'model'     => "App\Models\City",
        ]);

        CRUD::addColumn([
            'label' => 'Роль',
            'name' => 'role',
            'type' => 'enum',
            'options' => [
                'user' => 'Пользователь',
                'admin' => 'Админ',
            ],
        ]);

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
        CRUD::setValidation(UserRequest::class);

        $this->setupUpdateOperation();
        
        CRUD::addField([
            'name' => 'password',
            'label' => 'Пароль',
            'type' => 'password',
        ]);

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
        CRUD::addField([
            'name' => 'photo_url',
            'label' => 'Фото',
            'type' => 'upload',
            'upload' => true,
            'disk' => 'public',
        ]);
        CRUD::addField([
            'name' => 'name',
            'label' => 'Имя',
            'type' => 'text',
        ]);
        CRUD::addField([
            'name' => 'phone',
            'label' => 'Телефон',
            'type' => 'text',
        ]);
        CRUD::addField([
            'name' => 'email',
            'label' => 'Email',
            'type' => 'text',
        ]);
        CRUD::addField([
            'name' => 'is_phone_confirmed',
            'label' => 'Подтверждён',
            'type' => 'boolean',
        ]);
        CRUD::addField([
            'label'     => 'Город',
            'type'      => 'select',
            'name'      => 'city_id',
            'entity'    => 'city',
            'attribute' => 'name',
            'model'     => "App\Models\City",
        ]);

        CRUD::addField([
            'label' => 'Роль',
            'name' => 'role',
            'type' => 'enum',
            'options' => [
                'user' => 'Пользователь',
                'admin' => 'Админ',
            ],
        ]);
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }
}
