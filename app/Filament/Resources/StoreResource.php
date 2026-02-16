<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Models\Store;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Textarea::make('address')
                    ->rows(2)
                    ->columnSpanFull(),

                TimePicker::make('close_time')
                    ->seconds(false)
                    ->helperText('Kosong = 23:59'),

                // view field untuk map picker + ORS search
                ViewField::make('location')
                    ->view('filament.components.map-picker')
                    ->columnSpanFull()
                    ->dehydrated(false), // jangan disimpan sebagai field "location"

                TextInput::make('lat')
                    ->label('Latitude')
                    ->dehydrated(),

                TextInput::make('lng')
                    ->label('Longitude')
                    ->dehydrated(),

            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Toko')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('close_time')
                    ->label('Tutup')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 5) : '23:59')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lat')
                    ->label('Lat')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('lng')
                    ->label('Lng')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
