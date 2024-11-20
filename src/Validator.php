<?php

namespace SlimHexlet;

class Validator
{
    public function validate(array $user)
    {
        $errors = [];
        if ($user['nickname'] === '') {
            $errors['nickname'] = "Can't be blank";
        } elseif (strlen($user['nickname']) < 4) {
            $errors['nickname'] = "Nickname must be grater than 3 characters";
        }

        if ($user['email'] === '') {
            $errors['email'] = "Can't be blank";
        }

        return $errors;
    }
}