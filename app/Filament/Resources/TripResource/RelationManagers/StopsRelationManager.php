<?php

namespace App\Filament\Resources\TripResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


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
                    'skipped' => 'reject',
                ])
                ->required()
                ->live(),

            Forms\Components\Textarea::make('skip_reason')
                ->label('Alasan reject')
                ->rows(3)
                ->visible(fn(Forms\Get $get) => $get('status') === 'skipped')
                ->required(fn(Forms\Get $get) => $get('status') === 'skipped'),

            Forms\Components\TextInput::make('sequence')
                ->numeric()
                ->helperText('Nanti akan diisi otomatis saat generate route (ORS).')
                ->disabled(),

            Forms\Components\DateTimePicker::make('eta_at')
                ->label('ETA')
                ->seconds(false)
                ->disabled(),

            Forms\Components\DateTimePicker::make('close_at')
                ->label('Close At')
                ->seconds(false)
                ->disabled(),
        ]);
    }

    protected function googleMapsUrlFromCurrentLocation($record): string
    {
        $store = $record->store;

        $destLat = (float) ($store->lat ?? 0);
        $destLng = (float) ($store->lng ?? 0);

        if (! $destLat || ! $destLng) {
            // fallback kalau koordinat kosong
            return "https://www.google.com/maps/search/?api=1&query=" . urlencode($store->address ?? $store->name ?? '');
        }

        // ✅ tanpa origin → Google Maps pakai "Your location"
        return "https://www.google.com/maps/dir/?api=1"
            . "&destination={$destLat},{$destLng}"
            . "&travelmode=driving&dir_action=navigate";
    }


    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with('store'))

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
                    ->limit(40)
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
                                return "Rejected {$skip} — {$short}";
                            }

                            return "Rejected {$skip}";
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
                            $t = 'Rejected: ' . Carbon::parse($record->skipped_at)->format('d M Y H:i');
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

                Tables\Columns\TextColumn::make('arrived_to_finish')
                    ->label('Durasi Arrived')
                    ->state(function ($record) {
                        $minutes = $record->arrivedToFinishMinutes();
                        if ($minutes === null) return '—';

                        $hours = intdiv($minutes, 60);
                        $mins = $minutes % 60;

                        if ($hours > 0 && $mins > 0) return "{$hours}j {$mins}m";
                        if ($hours > 0) return "{$hours}j";
                        return "{$mins}m";
                    })
                    ->toggleable(),



                Tables\Columns\TextColumn::make('store.close_time')
                    ->label('Jam Tutup')
                    ->formatStateUsing(fn($state) => $state ? substr($state, 0, 5) : '23:59')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('skip_reason')
                    ->label('Reject reason')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('arrived_at')
                    ->label('Arrived At')
                    ->dateTime('d M H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('done_at')
                    ->label('Done At')
                    ->dateTime('d M H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('skipped_at')
                    ->label('Rejected At')
                    ->dateTime('d M H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->actions([
                Action::make('gmaps')
                    ->label('')
                    ->icon('heroicon-o-map')
                    ->color('blue')
                    ->size(ActionSize::Small)
                    ->tooltip('Navigasi via Google Maps')
                    ->url(fn($record) => $this->googleMapsUrlFromCurrentLocation($record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('arrived')
                    ->label('Arrived')
                    ->icon('heroicon-o-map-pin')
                    ->color('warning')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->action(fn($record) => $record->update([
                        'status' => 'arrived',
                        'arrived_at' => $record->arrived_at ?? now(),
                    ])),

                Tables\Actions\Action::make('done')
                    ->label('Done')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'arrived']))
                    ->action(fn($record) => $record->update([
                        'status' => 'done',
                        'done_at' => now(),
                    ])),

                Tables\Actions\Action::make('skip')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'arrived']))
                    ->form([
                        Forms\Components\Textarea::make('skip_reason')
                            ->required()
                            ->label('Alasan Reject'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'skipped',
                            'skip_reason' => $data['skip_reason'],
                            'skipped_at' => now(),
                        ]);
                    }),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => $record->trip?->status === 'planned'),
                Tables\Actions\EditAction::make()
                    ->disabled(fn($record) => $record->trip?->status === 'done'),

            ]);
    }
}
