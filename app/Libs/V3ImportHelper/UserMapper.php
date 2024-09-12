<?php

namespace App\Libs\V3ImportHelper;

use App\Models\User;

class UserMapper
{
    protected $users = [

    ];

    public function __construct()
    {
    }

    public function getUserFromDsUser(array $user_data): User
    {
        if ($this->users[$user_data['email']] ?? false) {
            return $this->users[$user_data['email']];
        }
        $existed = User::where('email', $user_data['email'])->first();
        if ($existed) {
            $this->users[$user_data['email']] = $existed;

            return $existed;
        }
        $user = new User();
        $user->name = $user_data['name'];
        $user->email = $user_data['email'];
        $user->password = $user_data['password'];
        $user->created_at = $user_data['created_at'];
        $user->updated_at = $user_data['updated_at'];
        $user->save();
        $this->users[$user_data['email']] = $user;

        return $user;
    }
}
