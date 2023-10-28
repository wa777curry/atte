<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atte</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}" />
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
            <div class="date__text">
                <button class="date__mark">
                    @if ($prevDate)
                    <a href="{{ route('attendance.date', ['date' => $prevDate->toDateString()]) }}">＜</a>
                    @endif
                </button>
                {{ $today->toDateString() }}
                <button class="date__mark">
                    @if ($nextDate)
                    <a href="{{ route('attendance.date', ['date' => $nextDate->toDateString()]) }}">＞</a>
                    @endif
                </button>
            </div>
            <div class="date__content">
                <div class="date__content--title">
                    名前
                </div>
                <div class="date__content--title">
                    勤務開始
                </div>
                <div class="date__content--title">
                    勤務終了
                </div>
                <div class="date__content--title">
                    休憩時間
                </div>
                <div class="date__content--title">
                    勤務時間
                </div>
            </div>
            @if ($datebases->count() > 0)
            @foreach ($datebases as $datebase)
            <div class="date__content">
                <div class="date__content--record">
                    {{ Auth::user()->name }}
                </div>
                <div class="date__content--record">
                    {{ $datebase->start_time }}
                </div>
                <div class="date__content--record">
                    {{ $datebase->end_time }}
                </div>
                <div class="date__content--record">
                    {{ $datebase->rest_time }}
                </div>
                <div class="date__content--record">
                    {{ $datebase->work_time }}
                </div>
            </div>
            @endforeach
            @else
            <p>出勤データがありません。</p>
            @endif

            <div class="pagination">
                
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer__logo">
            Atte,inc.
        </div>
    </footer>
</body>

</html>