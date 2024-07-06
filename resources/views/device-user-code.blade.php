@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Device Authorization') }}</div>

                    <div class="card-body">
                        {{ __('Enter the code displayed on your device.') }}

                        <form method="get" action="{{ route('passport.device.authorizations.authorize') }}">
                            <div class="row mb-3">
                                <label for="user-code" class="col-md-4 col-form-label text-md-end">{{ __('Code') }}</label>

                                <div class="col-md-6">
                                    <input id="user-code" type="text" class="form-control @error('user_code') is-invalid @enderror" name="user_code" value="{{ old('user_code') }}" required autofocus>

                                    @error('user_code')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Continue') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
