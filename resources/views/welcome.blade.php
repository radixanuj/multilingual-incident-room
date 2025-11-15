<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="refresh" content="0; url=/dashboard">
        <title>Redirecting to Dashboard...</title>
    </head>
    <body>
        <p>If you are not automatically redirected, <a href="/dashboard">click here</a>.</p>
        <script>window.location.href = '/dashboard';</script>
    </body>
</html>
