<?php

namespace App\Enums;

enum ServiceCategory: string
{
    case Electricidad = 'electricidad';
    case Plomeria = 'plomeria';
    case Construccion = 'construccion';
    case Remodelacion = 'remodelacion';
    case Pintura = 'pintura';
    case Carpinteria = 'carpinteria';
    case Techos = 'techos';
    case Jardineria = 'jardineria';
    case Limpieza = 'limpieza';
    case MudanzasYTransporte = 'mudanzas_transporte';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Electricidad => 'Electricidad',
            self::Plomeria => 'Plomería',
            self::Construccion => 'Construcción',
            self::Remodelacion => 'Remodelación',
            self::Pintura => 'Pintura',
            self::Carpinteria => 'Carpintería',
            self::Techos => 'Techos',
            self::Jardineria => 'Jardinería',
            self::Limpieza => 'Limpieza',
            self::MudanzasYTransporte => 'Mudanzas y transporte',
            self::Otro => 'Otro',
        };
    }
}
