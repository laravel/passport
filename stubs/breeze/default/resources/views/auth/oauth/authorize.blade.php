<x-guest-layout>
    <div class="mb-4 text-gray-600 text-center">
        <p><strong>{{ $user->name }}</strong></p>
        <p class="text-sm">{{ $user->email }}</p>
    </div>

    <div class="mb-4 text-sm text-gray-600">
        {{ __(':client is requesting permission to access your account.', ['client' => $client->name]) }}
    </div>

    @if (count($scopes) > 0)
        <div class="mb-4 text-sm text-gray-600">
            <p class="pb-1">{{ __('This application will be able to:') }}</p>

            <ul class="list-inside list-disc">
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-row-reverse gap-3 mt-4 flex-wrap items-center">
        <form method="POST" action="{{ route('passport.authorizations.approve') }}">
            @csrf

            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">

            <x-primary-button>
                {{ __('Authorize') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')

            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">

            <x-secondary-button type="submit">
                {{ __('Decline') }}
            </x-secondary-button>
        </form>

        <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ $request->fullUrlWithQuery(['prompt' => 'login']) }}">
            {{ __('Sign in with a different account') }}
        </a>
    </div>
</x-guest-layout>
