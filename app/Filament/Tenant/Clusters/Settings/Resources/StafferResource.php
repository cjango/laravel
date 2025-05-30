<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources;

use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Tenant\Clusters\Settings;
use App\Filament\Tenant\Clusters\Settings\Resources\StafferResource\Pages\ManageStaffers;
use App\Filament\Tenant\Clusters\Settings\Resources\StafferResource\Pages\ViewStaffer;
use App\Filament\Tenant\Clusters\Settings\Resources\StafferResource\RelationManagers\RecordsRelationManager;
use App\Models\Administrator;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StafferResource extends Resource
{
    protected static ?string $model = Administrator::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username')
                    ->label('用户名')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('姓名')
                    ->required(),
                TextInput::make('password')
                    ->label('登录密码')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                    ->dehydrated(fn(?string $state): bool => filled($state))
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->rule(Password::default()),
                Select::make('role')
                    ->label('成员角色')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant())
                    )
                    ->dehydrated(false)
                    ->native(false)
                    ->required()
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->selectablePlaceholder(false),
                CustomUpload::make('avatar')
                    ->label('头像')
                    ->avatar()
                    ->imageEditor()
                    ->imageResizeTargetWidth(200)
                    ->imageResizeTargetHeight(200),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular(),
                TextColumn::make('username')
                    ->label('用户名')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->label('角色'),
                IconColumn::make('status')
                    ->label('状态'),
                TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStaffers::route('/'),
            'view' => ViewStaffer::route('/{record}'),
        ];
    }
}
