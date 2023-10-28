<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atte</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/stamp.css') }}" />
</head>

<body>
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                Atte
            </div>
            <nav class="header__menu">
                <ul>
                    <li><a href="{{ route('stamp') }}">ホーム</a></li>
                    <li><a href="{{ route('attendance') }}">日付一覧</a></li>
                    <li><a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">ログアウト</a></li>
                    <form id="logout-form" action="/logout" method="POST" style="display: none;">
                        @csrf
                    </form>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="main__content">
            <div class="greeting__text">
                {{ Auth::user()->name }}さんお疲れ様です！
            </div>

            <div class="time__content">
                <div class="time__form">
                    <form class="form" action="{{ route('start_time') }}" method="post">
                        @csrf
                        <button class="time__button" type="submit" name="start_time" {{ $startTimeButton ? '' : 'disabled' }}>勤務開始</button>
                    </form>
                </div>

                <div class="time__form">
                    <form class="form" action="{{ route('end_time') }}" method="post">
                        @csrf
                        <button class="time__button" type="submit" name="end_time" {{ $endTimeButton ? '' : 'disabled' }}>勤務終了</button>
                    </form>
                </div>

                <div class="time__form">
                    <form class="form" action="{{ route('start_rest') }}" method="post">
                        @csrf
                        <button class="time__button" type="submit" name="start_rest" {{ $startRestButton ? '' : 'disabled' }}>休憩開始</button>
                    </form>
                </div>

                <div class="time__form">
                    <form class="form" action="{{ route('end_rest') }}" method="post">
                        @csrf
                        <button class="time__button" type="submit" name="end_rest" {{ $endRestButton ? '' : 'disabled' }}>休憩終了</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer__logo">
            Atte,inc.
        </div>
    </footer>
</body>