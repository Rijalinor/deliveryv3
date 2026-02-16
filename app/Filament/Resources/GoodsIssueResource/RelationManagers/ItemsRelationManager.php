<?php

namespace App\Filament\Resources\GoodsIssueResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('pfi_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('store_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pfi_number')
            ->columns([
                Tables\Columns\TextColumn::make('pfi_number'),
                Tables\Columns\TextColumn::make('store_name'),
                Tables\Columns\TextColumn::make('address')->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
