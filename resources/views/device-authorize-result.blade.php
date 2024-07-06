@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ __($approved ? 'Success!' : 'Canceled!') }}
                    </div>

                    <div class="card-body">
                        {{ __($approved ? 'Continue on your device.' : 'Device authorization canceled.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
