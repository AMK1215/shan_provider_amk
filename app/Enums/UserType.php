<?php

namespace App\Enums;

enum UserType: int
{
    case Owner = 10;
    case Master = 20;
    case Agent = 30;
    case SubAgent = 40;
    case Player = 50;
    case SystemWallet = 60;

    public static function usernameLength(UserType $type): int
    {
        return match ($type) {
            self::Owner => 1,
            self::Master => 2,
            self::Agent => 3,
            self::SubAgent => 4,
            self::Player => 5,
            self::SystemWallet => 6,
        };
    }

    public static function childUserType(UserType $type): UserType
    {
        return match ($type) {
            self::Owner => self::Master,
            self::Master => self::Agent,
            self::Agent => self::SubAgent,
            self::SubAgent => self::Player,
            self::Player, self::SystemWallet => self::Player,
        };
    }
}
