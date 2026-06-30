{{-- This file is used to store sidebar items, inside the Backpack admin panel --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('charts') }}"><i class="nav-icon la la-chart-bar"></i> Статистика</a></li>
<hr>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('user') }}"><i class="nav-icon la la-users"></i> Пользователи</a></li>
<hr>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('executor') }}"><i class="nav-icon la la-user-alt"></i> Исполнители</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('store') }}"><i class="nav-icon la la-address-card"></i> Металлопрокат</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('service-type') }}"><i class="nav-icon la la-bookmark"></i> Виды услуг</a></li>
<hr>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('city') }}"><i class="nav-icon la la-city"></i> Города</a></li>
<hr>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('advert') }}"><i class="nav-icon la la-newspaper"></i> Объявления</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('ad-category') }}"><i class="nav-icon la la-bookmark"></i> Категории объявлений</a></li>
<hr>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('subscription') }}"><i class="nav-icon la la-dollar-sign"></i> Подписки</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('invoice') }}"><i class="nav-icon la la-file-invoice-dollar"></i> Счета</a></li>
<hr>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('order-category') }}"><i class="nav-icon la la-bookmark"></i> Категории заказов</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('order') }}"><i class="nav-icon la la-shopping-cart"></i> Заказы</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('order-offer') }}"><i class="nav-icon la la-clipboard-list"></i> Предложения к заказам</a></li>