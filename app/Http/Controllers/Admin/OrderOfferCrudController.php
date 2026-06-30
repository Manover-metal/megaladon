<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OrderOfferRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class OrderOfferCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OrderOfferCrudController extends CrudController
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
        CRUD::setModel(\App\Models\OrderOffer::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/order-offer');
        CRUD::setEntityNameStrings('пердожение к заказу', 'предложения к заказам');
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
            'label'     => 'Заказ', // Table column heading
            'type'      => 'select',
            'name'      => 'order_id', // the column that contains the ID of that connected entity;
            'entity'    => 'order', // the method that defines the relationship in your Model
            'attribute' => 'title', // foreign key attribute that is shown to user
            'model'     => "App\Models\Order", // foreign key model
        ]);
        CRUD::addColumn([
            'label'     => 'Пользователь', // Table column heading
            'type'      => 'select',
            'name'      => 'user_id', // the column that contains the ID of that connected entity;
            'entity'    => 'user', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\User", // foreign key model
        ]);
        CRUD::addColumn([
            'label'     => 'Город', // Table column heading
            'type'      => 'select',
            'name'      => 'city_id', // the column that contains the ID of that connected entity;
            'entity'    => 'city', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\City", // foreign key model
        ]);
        CRUD::column('price')->label('Цена');
        CRUD::column('date')->label('Срок');
        CRUD::column('comment')->label('Комментарий');
        CRUD::column('expired_at')->label('Истекает');

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
        CRUD::setValidation(OrderOfferRequest::class);

        CRUD::addField([
            'label'     => 'Заказ', // Table column heading
            'type'      => 'select',
            'name'      => 'order_id', // the column that contains the ID of that connected entity;
            'entity'    => 'order', // the method that defines the relationship in your Model
            'attribute' => 'title', // foreign key attribute that is shown to user
            'model'     => "App\Models\Order", // foreign key model
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
        CRUD::field('price')->label('Цена');
        CRUD::field('date')->label('Срок');
        CRUD::field('comment')->label('Комментарий');
        CRUD::field('expired_at')->label('Истекает');

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
            'name' => 'created_at',
            'label' => 'Создан',
        ]);
        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => 'Обновлён',
        ]);
    }
}
