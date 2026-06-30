<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OrderCategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class OrderCategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OrderCategoryCrudController extends CrudController
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
        CRUD::setModel(\App\Models\OrderCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/order-category');
        CRUD::setEntityNameStrings('категория заказа', 'категории заказов');
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
            'name' => 'title',
            'label' => 'Наименование',
            'type' => 'text',
        ]);
        CRUD::addColumn([
            'label'     => 'Родитель', // Table column heading
            'type'      => 'select',
            'name'      => 'parent_id', // the column that contains the ID of that connected entity;
            'entity'    => 'parent', // the method that defines the relationship in your Model
            'attribute' => 'title', // foreign key attribute that is shown to user
            'model'     => "App\Models\OrderCategory", // foreign key model
            'allows_null'  => true,
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
        CRUD::setValidation(OrderCategoryRequest::class);

        CRUD::addField([
            'name' => 'title',
            'label' => 'Наименование',
            'type' => 'text',
        ]);
        CRUD::addField([
            'label'     => 'Родитель', // Table column heading
            'type'      => 'select',
            'name'      => 'parent_id', // the column that contains the ID of that connected entity;
            'entity'    => 'parent', // the method that defines the relationship in your Model
            'attribute' => 'title', // foreign key attribute that is shown to user
            'model'     => "App\Models\OrderCategory", // foreign key model
            'allows_null'  => true,
            'options'   => (function ($query) {
                return $query->orderBy('title', 'ASC')->where('parent_id', NULL)->get();
            }),
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
}
