@include('authsodium::style')

@if($payload->logo)
    <img src='{{ $payload->logo}}' />
@endif

<br /><br />

<h3>{{ __('authsodium::messages.success') }}!</h3>
<p> {!! __('authsodium::messages.email_verified_successfully', ['email' => $payload->user->email]) !!}</p>
<p> {!! __('authsodium::messages.click_to_sign_in', ['signInUrl' => $payload->signInUrl]) !!}</p>

