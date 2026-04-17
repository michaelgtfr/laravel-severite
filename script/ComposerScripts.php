<?php

namespace SeveriteScript;

class ComposerScripts
{
    public const YELLOW = "\e[33m";
    public const RESET = "\e[0m";

    public static function postInstall(): void
    {
        echo "\n";
        echo "✅ Severite installé avec succès !\n";
        echo "\n";
        echo "Lancez la commande suivante pour terminer la configuration :\n";
        echo "\n";
        echo self::YELLOW. "  artisan severite:install". self::RESET. "\n";
        echo "\n";
        echo "Si vous choissisez le stockage en bdd, faite cette commande pour crée la migration nécessaire:\n";
        echo "\n";
        echo self::YELLOW. "  artisan severite:migration". self::RESET. "\n";
        echo "\n";
    }
}
