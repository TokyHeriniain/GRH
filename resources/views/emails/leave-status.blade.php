<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Notification congé</title>
</head>
<body>
    <p>Bonjour {{ $leave->user->name }},</p>
    <p>Votre demande de congé du <strong>{{ $leave->start_date }}</strong> au <strong>{{ $leave->end_date }}</strong> a été <strong>{{ $leave->status }}</strong>.</p>
    <p>Merci.</p>
</body>
</html>