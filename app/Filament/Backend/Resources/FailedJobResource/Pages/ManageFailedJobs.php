<?php

namespace App\Filament\Backend\Resources\FailedJobResource\Pages;

use App\Filament\Backend\Resources\FailedJobResource;
use App\Models\FailedJob;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;

class ManageFailedJobs extends ManageRecords
{
    protected static string $resource = FailedJobResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function(Builder $query): Builder {
                return $query->orderByDesc('id');
            })
            ->columns([
                Tables\Columns\TextColumn::make('payload.displayName')
                    ->label('任务名称')
                    ->description(fn(FailedJob $record): string => $record->uuid),
                Tables\Columns\TextColumn::make('queue')
                    ->label('队列名称')
                    ->badge(),
                Tables\Columns\TextColumn::make('connection')
                    ->label('链接')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'redis' => 'danger',
                        'database' => 'success',
                    }),
                Tables\Columns\TextColumn::make('failed_at')
                    ->label('失败时间'),
            ])
            ->actions([
                Tables\Actions\Action::make('retry')
                    ->label('重试')
                    ->icon('heroicon-c-arrow-path-rounded-square')
                    ->requiresConfirmation()
                    ->action(function(FailedJob $record, Actions\Action $action) {
                        Artisan::call('queue:retry '.$record->uuid);
                        $action->successNotificationTitle('操作成功');
                        $action->success();
                    })
                    ->visible(fn() => userCan('retry', $this->getModel())),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_retry')
                    ->label('批量重试')
                    ->requiresConfirmation()
                    ->visible(fn() => userCan('bulkRetry', $this->getModel()))
                    ->action(function(Collection $records, Tables\Actions\BulkAction $action) {
                        $uuids = implode(' ', $records->pluck('uuid')->toArray());
                        Artisan::call('queue:retry '.$uuids);
                        $action->successNotificationTitle('操作成功');
                        $action->success();
                    }),
            ]);
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('clean')
                ->label('清理失败任务')
                ->icon('heroicon-o-trash')
                ->color(Color::Red)
                ->requiresConfirmation()
                ->visible(fn() => userCan('clean', $this->getModel()))
                ->action(function(Actions\Action $action) {
                    Artisan::call('queue:flush');
                    $action->successNotificationTitle('操作成功');
                    $action->success();
                }),
            Actions\Action::make('retryAll')
                ->label('重试所有失败任务')
                ->icon('heroicon-o-receipt-refund')
                ->color(Color::Green)
                ->visible(fn() => userCan('retryAll', $this->getModel()))
                ->requiresConfirmation()
                ->action(function(Actions\Action $action) {
                    Artisan::call('queue:retry all');
                    $action->successNotificationTitle('操作成功');
                    $action->success();
                }),
            Actions\Action::make('retryQueue')
                ->label('重试指定队列')
                ->icon('heroicon-m-arrows-pointing-out')
                ->visible(fn() => userCan('retryQueue', $this->getModel()))
                ->form(function() {
                    return [
                        Forms\Components\Select::make('name')
                            ->label('队列名')
                            ->required()
                            ->native(false)
                            ->options(FailedJob::select('queue')->distinct()->pluck('queue', 'queue')),
                    ];
                })
                ->action(function(array $data, Actions\Action $action) {
                    Artisan::call('queue:retry --queue='.$data['name']);
                    $action->successNotificationTitle('操作成功');
                    $action->success();
                }),
        ];
    }
}
