@extends('layouts.auth')

@section('title', 'Vérifier l\'e-mail')

@section('content')
    <div class="nt-auth-heading">
        <span class="nt-auth-icon"><i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i></span>
        <div>
            <span class="nt-auth-kicker">Étape obligatoire</span>
            <span class="nt-auth-kicker-sub"><i class="fa-solid fa-clock" aria-hidden="true"></i> Lien valable {{ $expireMinutes ?? config('auth.verification.expire', 60) }} min</span>
        </div>
    </div>

    <h1 class="nt-auth-title">Confirmez votre e-mail</h1>
    <p class="nt-auth-subtitle">
        Nous avons envoyé un message à <strong>{{ auth()->user()->email }}</strong>.
        Ouvrez-le et cliquez sur le lien pour activer votre compte NutriTrace.
    </p>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mb-2">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg btn-block">Renvoyer l'e-mail</button>
    </form>

    <p class="small text-muted text-center mb-3">
        Pensez à vérifier les spams / courrier indésirable.
    </p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-link btn-block">Se déconnecter</button>
    </form>
@endsection
