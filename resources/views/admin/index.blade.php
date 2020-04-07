<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        <p>当前在线人数 {{ $data['total'] }} 人</p>
        <table>
            <th>用户 ID</th>
            <th>用户名</th>
            <th>IP</th>
                @foreach ($data['list'] as $v)
                <tr>
                    <td>{{ $v->id }}</td>
                    <td>{{ $v->nickname }}</td>
                    <td>{{ long2ip($v->ip) }}</td>
                </tr>
                @endforeach
            @if(!empty($data['error']))
            <tr>
                <td><p style="color: red;">{{ $data['error'] }}</p></td>
            </tr>
            @endif
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
