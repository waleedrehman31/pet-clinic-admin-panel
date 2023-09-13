<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Role;
use App\Models\Slot;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Appointment;
use Filament\Facades\Filament;
use App\Enums\AppointmentStatus;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AppointmentResource\Pages;
use App\Filament\Resources\AppointmentResource\RelationManagers;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Form $form): Form
    {
        $doctorRole = Role::whereName('doctor')->first();
        return $form
            ->schema([
                Forms\Components\Section::make(
                    [
                        Forms\Components\Select::make('pet_id')
                            ->required()
                            ->relationship('pet', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->native(false)
                            ->live(),
                        Forms\Components\Select::make('doctor_id')
                            ->options(function (Get $get) use ($doctorRole) {
                                Filament::getTenant()
                                    ->users()
                                    ->whereBelongsTo($doctorRole)
                                    ->whereHas('schedules', function(Builder $query) use($get) {
                                        $query->where('date', $get('date'));
                                    })
                                    ->get()
                                    ->pluck('name', 'id');
                            })
                            ->hidden(fn (Get $get) => blank($get('date'))),
                        Forms\Components\Select::make('slot_id')
                            ->native(false)
                            ->relationship(name: 'slot', titleAttribute: 'start')
                            ->getOptionLabelFromRecordUsing(fn (Slot $record) => $record->start->format('h:i  A')),
                        Forms\Components\TextInput::make('description')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->native(false)
                            ->options(AppointmentStatus::class)
                            ->visibleOn(Pages\EditAppointment::class),
                    ]
                )
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pet.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date('M d Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start')
                    ->time('h:i A')
                    ->label('From')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end')
                    ->time('h:i A')
                    ->label('To')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Confirm')
                    ->action(function (Appointment $record) {
                        $record->status = AppointmentStatus::Confirmed;
                        $record->save();
                    })
                    ->visible(fn (Appointment $record) => $record->status == AppointmentStatus::Created)
                    ->color('success')
                    ->icon('heroicon-o-check'),
                Tables\Actions\Action::make('Cancel')
                    ->action(function (Appointment $record) {
                        $record->status = AppointmentStatus::Canceled;
                        $record->save();
                    })
                    ->visible(fn (Appointment $record) => $record->status != AppointmentStatus::Canceled)
                    ->color('danger')
                    ->icon('heroicon-o-x-mark'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
