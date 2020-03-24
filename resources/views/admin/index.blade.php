<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
{{--    <link rel="shortcut icon" href="https://cdn.learnku.com//uploads/communities/WtC3cPLHzMbKRSZnagU9.png!/both/44x44"/>--}}
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', '聊天后台')</title>

    <!-- Styles -->
{{--    <link href="{{ mix('css/app.css') }}" rel="stylesheet">--}}

</head>

<body>
<div id="app" class="{{ route_class() }}-page">

{{--    @include('layouts._header')--}}

    <div class="container">
        <p>当前在线人数 {{ $list['total'] }} 人</p>
        <table style="border: 1px solid black;">
            <th>用户 ID</th>
            <th>用户名</th>
                @foreach ($list['list'] as $v)
                <tr>
                    <td>{{ $v['user_id'] }}</td>
                    <td>{{ $v['username'] }}</td>
                </tr>
                @endforeach
        </table>
{{--        @include('shared._messages')--}}

        @yield('content')

    </div>

{{--    @include('layouts._footer')--}}
</div>

<!-- Scripts -->
{{--<script src="{{ mix('js/app.js') }}"></script>--}}
</body>

</html>
