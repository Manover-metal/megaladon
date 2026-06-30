@extends(backpack_view('blank'))

@section('header')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="{{asset('css/counter-cards.css')}}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" />
@endsection

@section('content')
<div class="container">
    <h1>Статистика</h1>
    <div class="row">
        <div class="col-md-3">
            <div class="card-counter info">
            <i class="fa fa-users"></i>
            <span class="count-numbers">{{$countUsers}}</span>
            <span class="count-name">Пользователей</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-counter primary">
            <i class="fa fa-shopping-cart"></i>
            <span class="count-numbers">{{$countOrders}}</span>
            <span class="count-name">Заказов</span>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-counter danger">
            <i class="fa fa-newspaper-o"></i>
            <span class="count-numbers">{{$countAdverts}}</span>
            <span class="count-name">Объявлений</span>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-counter success">
            <i class="fa fa-address-card"></i>
            <span class="count-numbers">{{$countExecutors}}</span>
            <span class="count-name">Исполнителей</span>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-counter info">
            <i class="fa fa-address-card"></i>
            <span class="count-numbers">{{$countStores}}</span>
            <span class="count-name">Магазинов</span>
            </div>
        </div>
    </div>

    <hr>

    <div class="row">
        <div class="col-6">
            <h3>Пользователи</h3>
            <div>
                <canvas id="users"></canvas>
            </div>
        </div>
        <div class="col-6">
            <h3>Заказы</h3>
            <div>
                <canvas id="orders"></canvas>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-6">
            <h3>Объявления</h3>
            <div>
                <canvas id="ads"></canvas>
            </div>
        </div>
        <div class="col-6">
            <h3>Исполнители</h3>
            <div>
                <canvas id="executors"></canvas>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-6">
            <h3>Магазины</h3>
            <div>
                <canvas id="stores"></canvas>
            </div>
        </div>
    </div>
</div>

    <script>
    const users = document.getElementById('users');

    new Chart(users, {
        type: 'bar',
        data: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июнь', 'Июль', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
        datasets: [{
            label: 'Пользователей за месяц',
            data: [{{ implode(', ', $users) }}],
            borderWidth: 1
        }]
        },
        options: {
        scales: {
            y: {
            beginAtZero: true
            }
        }
        }
    });

    const orders = document.getElementById('orders');

    new Chart(orders, {
        type: 'bar',
        data: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июнь', 'Июль', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
        datasets: [{
            label: 'Заказов за месяц',
            data: [{{ implode(', ', $orders) }}],
            borderWidth: 1
        }]
        },
        options: {
        scales: {
            y: {
            beginAtZero: true
            }
        }
        }
    });

    const ads = document.getElementById('ads');

    new Chart(ads, {
        type: 'bar',
        data: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июнь', 'Июль', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
        datasets: [{
            label: 'Объявлений за месяц',
            data: [{{ implode(', ', $adverts) }}],
            borderWidth: 1
        }]
        },
        options: {
        scales: {
            y: {
            beginAtZero: true
            }
        }
        }
    });

    const executors = document.getElementById('executors');

    new Chart(executors, {
        type: 'bar',
        data: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июнь', 'Июль', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
        datasets: [{
            label: 'Исполниелей зарегистрированно',
            data: [{{ implode(', ', $executors) }}],
            borderWidth: 1
        }]
        },
        options: {
        scales: {
            y: {
            beginAtZero: true
            }
        }
        }
    });

    const stores = document.getElementById('stores');

    new Chart(stores, {
        type: 'bar',
        data: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июнь', 'Июль', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
        datasets: [{
            label: 'Исполниелей зарегистрированно',
            data: [{{ implode(', ', $stores) }}],
            borderWidth: 1
        }]
        },
        options: {
        scales: {
            y: {
            beginAtZero: true
            }
        }
        }
    });
    </script>
@endsection