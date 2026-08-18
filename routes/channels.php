<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Mismo criterio de acceso que la middleware role:ROL001 usa para /utilitarios
$puedeVerInscripciones = fn ($user) => $user->rol_id === 'ROL001'
    || in_array('utilitarios', $user->modulos_json ?? []);

Broadcast::channel('evento.{eventoId}.inscripciones', function ($user, $eventoId) use ($puedeVerInscripciones) {
    return $puedeVerInscripciones($user);
});

Broadcast::channel('inscripciones', function ($user) use ($puedeVerInscripciones) {
    return $puedeVerInscripciones($user);
});
