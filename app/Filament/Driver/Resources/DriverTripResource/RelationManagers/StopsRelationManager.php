<?php

namespace App\Filament\Driver\Resources\DriverTripResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class StopsRelationManager extends RelationManager
{
    protected static string $relationship = 'stops';
    protected static ?string $title = 'Stops';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'pending',
                    'arrived' => 'arrived',
                    'done' => 'done',
                    'skipped' => 'skipped',
                ])
                ->required()
                ->live(),

            Forms\Components\Textarea::make('skip_reason')
                ->label('Alasan skip')
                ->rows(3)
                ->visible(fn(Forms\Get $get) => $get('status') === 'skipped')
                ->required(fn(Forms\Get $get) => $get('status') === 'skipped'),

            // tampil saja (nanti diisi saat generate ORS)
            Forms\Components\TextInput::make('sequence')
                ->numeric()
                ->disabled()
                ->helperText('Akan terisi otomatis saat generate rute.'),

            Forms\Components\DateTimePicker::make('eta_at')
                ->label('ETA')
                ->seconds(false)
                ->disabled(),

            Forms\Components\DateTimePicker::make('close_at')
                ->label('Tutup (hari ini)')
                ->seconds(false)
                ->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sequence')
            ->columns([
                Tables\Columns\TextColumn::make('sequence')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('Toko')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('store.address')
                    ->label('Alamat')
                    ->limit(45)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('actual_time')
                    ->label('Waktu')
                    ->state(function ($record) {
                        $done = $record->done_at ? Carbon::parse($record->done_at)->format('H:i') : null;
                        $skip = $record->skipped_at ? Carbon::parse($record->skipped_at)->format('H:i') : null;
                        $arr  = $record->arrived_at ? Carbon::parse($record->arrived_at)->format('H:i') : null;

                        if ($done) {
                            return "Done {$done}";
                        }

                        if ($skip) {
                            $reason = trim((string) ($record->skip_reason ?? ''));
                            if ($reason !== '') {
                                $short = Str::limit($reason, 18); // biar gak kepanjangan di tabel
                                return "Skipped {$skip} — {$short}";
                            }

                            return "Skipped {$skip}";
                        }

                        if ($arr) {
                            return "Arrived {$arr}";
                        }

                        return '—';
                    })
                    ->badge()
                    ->color(function ($record) {
                        if ($record->done_at) return 'success';
                        if ($record->skipped_at) return 'danger';
                        if ($record->arrived_at) return 'warning';
                        return 'gray';
                    })
                    ->tooltip(function ($record) {
                        // tooltip khusus skip biar alasan lengkap keliatan
                        if ($record->skipped_at) {
                            $t = 'Skipped: ' . Carbon::parse($record->skipped_at)->format('d M Y H:i');
                            if (!empty($record->skip_reason)) {
                                $t .= "\nAlasan: " . $record->skip_reason;
                            }
                            return $t;
                        }

                        if ($record->done_at) {
                            return 'Done: ' . Carbon::parse($record->done_at)->format('d M Y H:i');
                        }

                        if ($record->arrived_at) {
                            return 'Arrived: ' . Carbon::parse($record->arrived_at)->format('d M Y H:i');
                        }

                        return null;
                    }),

                Tables\Columns\TextColumn::make('store.close_time')
                    ->label('Jam Tutup')
                    ->formatStateUsing(fn($state) => $state ? substr($state, 0, 5) : '23:59')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('skip_reason')
                    ->label('Alasan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // driver TIDAK boleh create stop manual
            ->headerActions([])
            ->actions([

                Action::make('gmaps')
                    ->label('')
                    ->icon('heroicon-o-map')
                    ->color('blue')
                    ->size(ActionSize::Small)
                    ->tooltip('Navigasi via Google Maps')
                    ->url(fn($record) => $this->gmapsUrl($record))
                    ->openUrlInNewTab(),

                // Tombol cepat: Arrived
                // Tables\Actions\Action::make('arrived')
                //     ->label('Arrived')
                //     ->icon('heroicon-m-map-pin')
                //     ->visible(fn($record) => in_array($record->status, ['pending']))
                //     ->action(function ($record) {
                //         $record->update([
                //             'status' => 'arrived',
                //             'arrived_at' => now(),
                //         ]);

                //         Notification::make()->title('Status: arrived')->success()->send();
                //     }),

                // // Tombol cepat: Done
                // Tables\Actions\Action::make('done')
                //     ->label('Done')
                //     ->icon('heroicon-m-check-badge')
                //     ->visible(fn($record) => in_array($record->status, ['pending', 'arrived']))
                //     ->action(function ($record) {
                //         $record->update([
                //             'status' => 'done',
                //             'done_at' => now(),
                //         ]);

                //         Notification::make()->title('Status: done')->success()->send();
                //     }),

                // // Tombol cepat: Skip (pakai form alasan)
                // Tables\Actions\Action::make('skip')
                //     ->label('Skip')
                //     ->color('danger')
                //     ->icon('heroicon-m-x-circle')
                //     ->visible(fn($record) => in_array($record->status, ['pending', 'arrived']))
                //     ->form([
                //         Forms\Components\Textarea::make('skip_reason')
                //             ->label('Alasan skip')
                //             ->required()
                //             ->rows(3),
                //     ])
                //     ->action(function ($record, array $data) {
                //         $record->update([
                //             'status' => 'skipped',
                //             'skip_reason' => $data['skip_reason'],
                //         ]);

                //         Notification::make()->title('Stop di-skip')->warning()->send();
                //     }),

                // Edit manual (opsional) - kalau mau minimal aja, boleh hapus ini
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->visible(fn() => false), // biar driver gak utak-atik selain tombol cepat
            ])
            // driver tidak boleh bulk delete
            ->bulkActions([]);
    }

    protected function gmapsUrl($record): string
    {
        $store = $record->store;

        $lat = (float) ($store->lat ?? 0);
        $lng = (float) ($store->lng ?? 0);

        if (! $lat || ! $lng) {
            $q = urlencode($store->address ?? $store->name ?? '');
            return "https://www.google.com/maps/search/?api=1&query={$q}";
        }

        // ✅ tanpa origin -> "Your location"
        return "https://www.google.com/maps/dir/?api=1"
            . "&destination={$lat},{$lng}"
            . "&travelmode=driving"
            . "&dir_action=navigate";
    }
}
