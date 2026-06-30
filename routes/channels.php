<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Chat;
use App\Models\User;

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

Broadcast::channel('chat.{chat}', function (User $user, Chat $chat) {
    $membersIds = $chat->members()->pluck('user_id')->toArray();
    return (int) in_array($user->id, $membersIds);
});

Broadcast::channel('userChats.{user_id}', function (User $authUser, $user_id) {
    return $authUser->id == $user_id;
});
