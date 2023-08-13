<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppointmentStatus: string implements HasLabel, HasColor
{
  case Created = 'created';
  case Confirmed = 'confirmed';
  case Canceled = 'Canceled';

  public function getLabel(): ?string
  {
    return match ($this) {
      self::Created => 'Created',
      self::Confirmed => 'Confirmed',
      self::Canceled => 'Canceled',
    };
  }
  public function getColor(): string|array|null
  {
    return match ($this) {
      self::Created => 'warning',
      self::Confirmed => 'success',
      self::Canceled => 'danger'
    };
  }
}
