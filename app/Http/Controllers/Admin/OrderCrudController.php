<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OrderRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class OrderCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OrderCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Order::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/order');
        CRUD::setEntityNameStrings('заказ', 'заказы');
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
        CRUD::column('user.name')->label('Пользователь');
        CRUD::column('title')->label('Заголовок');
        CRUD::column('category.title')->label('Категория');
        CRUD::addColumn([
            'label'     => 'Город', // Table column heading
            'type'      => 'select',
            'name'      => 'city_id', // the column that contains the ID of that connected entity;
            'entity'    => 'city', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\City", // foreign key model
        ]);
        CRUD::addColumn([
            'name'  => 'status',
            'label' => 'Статус', // Table column heading
            'type'  => 'model_function',
            'function_name' => 'getStatusName',
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
        CRUD::setValidation(OrderRequest::class);

        CRUD::field('title')->label('Заголовок');
        CRUD::field('description')->label('Описание');
        CRUD::field('price_recommended')->label('Желаемая цена');
        CRUD::field('price_max')->label('Максимальная цена');
        CRUD::addField([
            'label'     => 'Категория', // Table column heading
            'type'      => 'select',
            'name'      => 'category_id', // the column that contains the ID of that connected entity;
            'entity'    => 'category', // the method that defines the relationship in your Model
            'attribute' => 'title', // foreign key attribute that is shown to user
            'model'     => "App\Models\OrderCategory", // foreign key model
        ]);
        CRUD::addField([
            'name' => 'status',
            'label' => 'Статус',
            'type' => 'select_from_array',
            'options' => [1 => 'На проверке', 2 => 'Активный', 3 => 'В работе', 4 => 'Завершенный', 5 => 'Архив'],
        ]);
        CRUD::addField([
            'label'     => 'Пользователь', // Table column heading
            'type'      => 'select',
            'name'      => 'user_id', // the column that contains the ID of that connected entity;
            'entity'    => 'user', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\User", // foreign key model
        ]);
        CRUD::addField([
            'label'     => 'Город', // Table column heading
            'type'      => 'select',
            'name'      => 'city_id', // the column that contains the ID of that connected entity;
            'entity'    => 'city', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\City", // foreign key model
        ]);
        CRUD::addField([
            'label'     => 'Исполнитель', // Table column heading
            'type'      => 'select',
            'name'      => 'executor_id', // the column that contains the ID of that connected entity;
            'entity'    => 'executor', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Executor", // foreign key model
            'allows_null'  => true,
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
        $this->setupCreateOperation();
    }

    protected function autoSetupShowOperation()
    {
        CRUD::column('id');
        CRUD::column('title')->label('заголовок');
        CRUD::column('description')->label('Описание');
        CRUD::column('price_recommended')->label('Желаемая цена');
        CRUD::column('price_max')->label('Максимальная цена');
        CRUD::column('category.title')->label('Категория');
        CRUD::addColumn([
            'name'  => 'status',
            'label' => 'Статус', // Table column heading
            'type'  => 'model_function',
            'function_name' => 'getStatusName',
        ]);
        CRUD::column('user_id');
        CRUD::column('city_id');
        CRUD::column('executor_id');
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
