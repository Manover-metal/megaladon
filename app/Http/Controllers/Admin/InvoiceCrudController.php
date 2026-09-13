<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\InvoiceRequest;
use App\Models\Invoice;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;

/**
 * Class InvoiceCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InvoiceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Invoice::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/invoice');
        CRUD::setEntityNameStrings('счет', 'счета');
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
        CRUD::column('invoiceable.name')->label('Владелец');
        CRUD::addColumn([
            'label'     => 'Подписка', // Table column heading
            'type'      => 'select',
            'name'      => 'subscription_id', // the column that contains the ID of that connected entity;
            'entity'    => 'subscription', // the method that defines the relationship in your Model
            'attribute' => 'full', // foreign key attribute that is shown to user
            'model'     => "App\Models\Subscription", // foreign key model
            'allows_null'  => true,
        ]);
        CRUD::column('subscription_id');
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Статус',
            'type' => 'select_from_array',
            'options' => [
                'CREATED' => 'Создано',
                'CANCELED' => 'Отменено',
                'PAID' => 'Оплачено',
                'EXPIRED' => 'Истекло',
            ],
        ]);
        CRUD::column('meta');
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
        CRUD::setValidation(InvoiceRequest::class);


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
    // Онлайн-оплаты в приложении нет: «купить подписку» создаёт инвойс со
    // статусом CREATED, и подписку включают здесь — ставят «Оплачено».
    // Срок можно не заполнять: посчитается от тарифа, см. update().
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        CRUD::addField([
            'name' => 'status',
            'label' => 'Статус',
            'type' => 'select_from_array',
            'options' => [
                Invoice::STATUS_CREATED => 'Создано',
                Invoice::STATUS_PAID => 'Оплачено',
                Invoice::STATUS_CANCELED => 'Отменено',
                Invoice::STATUS_EXPIRED => 'Истекло',
            ],
            'allows_null' => false,
        ]);
        CRUD::addField([
            'name' => 'expired_at',
            'label' => 'Истекает',
            'type' => 'date',
            'hint' => 'При отметке «Оплачено» можно оставить пустым — срок посчитается от тарифа: от сегодня или от конца текущей подписки.',
        ]);
    }

    /**
     * Отметили «Оплачено», а срок не указали — считаем его так же, как
     * InvoiceService::paid(): от конца другой действующей подписки того же
     * владельца, иначе от сегодня, плюс срок тарифа в месяцах.
     */
    public function update()
    {
        $request = $this->crud->getRequest();

        if ($request->input('status') === Invoice::STATUS_PAID && !$request->filled('expired_at')) {
            $invoice = Invoice::with('subscription', 'invoiceable')
                ->find($this->crud->getCurrentEntryId());

            if ($invoice && $invoice->subscription) {
                $request->merge(['expired_at' => $this->paidUntil($invoice)->toDateString()]);
            }
        }

        return $this->traitUpdate();
    }

    private function paidUntil(Invoice $invoice): Carbon
    {
        $current = $invoice->invoiceable
            ? $invoice->invoiceable->invoices()
                ->where('id', '!=', $invoice->id)
                ->where('status', Invoice::STATUS_PAID)
                ->whereDate('expired_at', '>', Carbon::now())
                ->orderBy('expired_at', 'desc')
                ->first()
            : null;

        $from = $current ? Carbon::parse($current->expired_at) : Carbon::now();

        return $from->addMonths($invoice->subscription->validity);
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
