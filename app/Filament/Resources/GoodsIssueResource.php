<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoodsIssueResource\Pages;
use App\Filament\Resources\GoodsIssueResource\RelationManagers;
use App\Models\GoodsIssue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GoodsIssueResource extends Resource
{
    protected static ?string $model = GoodsIssue::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('gi_number')->required(),
                Forms\Components\DatePicker::make('date'),
                Forms\Components\Select::make('status')
                    ->options(['open' => 'Open', 'assigned' => 'Assigned', 'completed' => 'Completed'])
                    ->default('open')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('gi_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'info',
                        'assigned' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Items'),
            ])
            ->filters([
                //
            ])
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
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsIssues::route('/'),
            'create' => Pages\CreateGoodsIssue::route('/create'),
            'edit' => Pages\EditGoodsIssue::route('/{record}/edit'),
        ];
    }
}
