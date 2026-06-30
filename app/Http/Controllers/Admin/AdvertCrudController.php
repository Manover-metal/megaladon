<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AdvertRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class AdvertCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AdvertCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Advert::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/advert');
        CRUD::setEntityNameStrings('объявление', 'объявления');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('id');
        CRUD::addColumn([
            'name' => 'type',
            'label' => 'Тип',
            'type' => 'select_from_array',
            'options' => [
                'service' => 'Услуги',
                'advert' => 'Объявление',
            ],
        ]);
        CRUD::addColumn([
            'label'     => 'Пользователь', // Table column heading
            'type'      => 'select',
            'name'      => 'user_id', // the column that contains the ID of that connected entity;
            'entity'    => 'user', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\User",
        ]);
        CRUD::column('title')->label('Заголовок');
        CRUD::column('category.name')->label('Категория');
        CRUD::column('city.name')->label('Город');
        CRUD::column('price')->label('Цена');
        CRUD::column('additional_phone')->label('Доп. номер');

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
        CRUD::setValidation(AdvertRequest::class);

        CRUD::addField([
            'label'     => 'Пользователь', // Table column heading
            'type'      => 'select',
            'name'      => 'user_id', // the column that contains the ID of that connected entity;
            'entity'    => 'user', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\User",
        ]);
        CRUD::addField([
            'name' => 'type',
            'label' => 'Тип',
            'type' => 'select_from_array',
            'options' => [
                'service' => 'Услуги',
                'advert' => 'Объявление',
            ],
        ]);
        CRUD::field('title')->label('Заголовок');
        CRUD::field('description')->label('Описание');
        CRUD::field('price')->label('Цена');
        CRUD::addField([
            'label'     => 'Категория', // Table column heading
            'type'      => 'select',
            'name'      => 'category_id', // the column that contains the ID of that connected entity;
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\AdCategory",
        ]);
        CRUD::addField([
            'label'     => 'Город', // Table column heading
            'type'      => 'select',
            'name'      => 'city_id', // the column that contains the ID of that connected entity;
            'entity'    => 'city', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\City",
        ]);
        CRUD::field('additional_phone')->label('Доп. телефон');

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
        
        CRUD::column('description')->label('Описание');
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
