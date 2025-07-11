<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FleaMarket</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    @yield('css')
</head>

<body>

    <header class="header">

        <div class="header__inner">

            <div class="logo">
                <img src="{{ asset('./img/logo.svg') }}" alt="coachtech">
            </div>

            {{-- 管理者としてログインしたとき --}}
            @if( Auth::guard('administrators')->check() )
                <nav>
                    <ul class="header-nav">
                        <li class="header-nav__item">
                            <form class="header-nav__button" action="/admin/attendances" method="GET">
                                @csrf
                                <button type="submit">退勤一覧</button>
                            </form>
                        </li>
                        <li class="header-nav__item">
                            <form class="header-nav__button" action="/admin/users" method="GET">
                                @csrf
                                <button type="submit">スタッフ一覧</button>
                            </form>
                        </li>
                        <li class="header-nav__item">
                            <form class="header-nav__button" action="/admin/requests" method="GET">
                                @csrf
                                <button type="submit">申請一覧</button>
                            </form>
                        </li>
                        <li class="header-nav__item">
                            <form class="header-nav__button" action="/admin/logout" method="GET">
                                @csrf
                                <button type="submit">ログアウト</button>
                            </form>
                        </li>
                    </ul>
                </nav>

            {{-- 一般ユーザとしてログインしたとき --}}
            @elseif( Auth::guard('members')->check() )
                <nav>
                    <ul class="header-nav">

                        @if ( Auth::guard('members')->user()->hasVerifiedEmail() )
                            <li class="header-nav__item">
                            <form class="header-nav__button" action="/attendance" method="GET">
                                @csrf
                                <button type="submit">退勤</button>
                            </form>
                            </li>
                            <li class="header-nav__item">
                                <form class="header-nav__button" action="/attendance/list" method="GET">
                                    @csrf
                                    <button type="submit">退勤一覧</button>
                                </form>
                            </li>
                            <li class="header-nav__item">
                                <form class="header-nav__button" action="/stamp_correction_request/list" method="GET">
                                    @csrf
                                    <button type="submit">申請</button>
                                </form>
                            </li>
                        @endif

                        <li class="header-nav__item">
                            <form class="header-nav__button" action="/logout" method="GET">
                                @csrf
                                <button type="submit">ログアウト</button>
                            </form>
                        </li>
                    </ul>
                </nav>

            @endif

        </div>

    </header>

    <main>
        @yield('content')
    </main>

</body>

</html>