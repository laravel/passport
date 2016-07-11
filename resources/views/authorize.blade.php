<html>
    <head>
        <title>Authorize</title>
    </head>
    <body>
        <div>
            {{ $client->name }} would like to access your account.

            <ul>
            @foreach ($scopes as $scope)
                <li>{{ $scope->id }}</li>
            @endforeach
            </ul>
        </div>

        <form method="post" action="/oauth/authorize">
            {{ csrf_field() }}
            {{ method_field('DELETE') }}

            <input type="hidden" name="state" value="{{ $request->input('state') }}">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="submit" value="Deny">
        </form>

        <form method="post" action="/oauth/authorize">
            {{ csrf_field() }}

            <input type="hidden" name="state" value="{{ $request->input('state') }}">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="submit" value="Approve">
        </form>
    </body>
</html>
