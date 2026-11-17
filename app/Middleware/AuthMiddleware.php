<?php
// app/Middleware/AuthMiddleware.php

class AuthMiddleware {
    public static function check() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
    }

    public static function role($role) {
        self::check();
        if ($_SESSION['user']['role'] != $role) {
            header('Location: /dashboard');
            exit;
        }
    }

    public static function roleIn($roles) {
        self::check();
        if (!in_array($_SESSION['user']['role'], $roles)) {
            header('Location: /dashboard');
            exit;
        }
    }
}