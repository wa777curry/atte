<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atte</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/login.css') }}" />
</head>

<body>
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                Atte
            </div>
        </div>
    </header>

    <main>
        <div class="main__content">
            <div class="main__title">
                ログイン
            </div>
            <form class="form" action="{{ route('login.submit') }}" method="post">
                @csrf
                <div class="form__group">
                    <div class="form__input--text">
                        <input type="email" name="email" placeholder="メールアドレス" value="{{ old('email') ?: session('email') }}">
                    </div>
                    <div class="form__error">
                        @error('email')
                        {{ $message }}
                        @enderror
                    </div>
                    <div class="form__input--text">
                        <input type="password" name="password" placeholder="パスワード">
                    </div>
                    <div class="form__error">
                        @error('password')
                        {{ $message }}
                        @enderror
                    </div>
                    @if ($errors->has('login'))
                        <div class="form__alert">
                            {{ $errors->first('login')}}
                        </div>
                    @endif
                    <div class="form__button">
                        <button class="form__button-submit">ログイン</button>
                    </div>
                </div>
            </form>
            <div class="note--text">
                アカウントをお持ちでない方はこちらから
            </div>
            <div class="note--link">
                <a href="{{ route('register.form') }}">会員登録</a>
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