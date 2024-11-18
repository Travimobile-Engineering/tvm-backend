<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Your Verification Code</title>
</head>
<body>
    <h1>Hello {{$user->full_name}}, </h1>
    <p>Thank you for creating an account with Travimobile. Use the code below to verify your email address:</p>
    <h2>{{$verification_code}}</h2>
    <p>The code is valid for 10 minutes</p>
</body>
</html>