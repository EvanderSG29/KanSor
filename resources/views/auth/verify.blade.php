@extends('layouts.app')

@section('title', __('Verify Your Email Address'))
@section('body_class', 'hold-transition login-page')
@section('auth_page', true)

@section('content')
<div class="login-box">
    <div class="login-logo">
        <a href="{{ url('/') }}"><b>{{ config('app.name', 'KanSor') }}</b></a>
    </div>

    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">{{ __('Verify Your Email Address') }}</p>

            @if (session('resent'))
                <div class="alert alert-success" role="alert">
                    {{ __('A fresh verification link has been sent to your email address.') }}
                </div>
            @endif

            <p>{{ __('Before proceeding, please check your email for a verification link.') }}</p>
            <p>{{ __('If you did not receive the email') }},</p>

            <form method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-block">{{ __('click here to request another') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
