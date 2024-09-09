<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use app\Models\UserManagement\User;
use App\Models\UserManagement\UserDetail;
use App\Models\UserManagement\UserGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('User Detail')
                ->schema([
                    TextInput::make('name')
                        ->placeholder('John Doe')
                        ->autocomplete('name')
                        ->required(),
                    TextInput::make('email')
                        ->email()
                        ->placeholder('john.doe@example.com')
                        ->autocomplete('email')
                        ->required(),
                    TextInput::make('phone')
                        ->tel()
                        ->placeholder('+62 123 456 7890')
                        ->autocomplete('phone')
                        ->required(),
                    TextInput::make('password')
                        ->placeholder('Password')
                        ->password()
                        ->autocomplete(false)
                        ->required(),
                ]),
            Section::make('User Detail')
                ->relationship('userDetail')
                ->schema([
                    Select::make('group_id')
                        ->relationship(name: 'userGroup', titleAttribute: 'name')
                        ->createOptionForm([
                            TextInput::make('name')
                                ->required(),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            $group = UserGroup::query()->create([
                                'name' => $data['name'],
                                'company_id' => auth()->user()->account_detail_id
                            ]);
                            return $group->getKey();
                        })
                        ->editOptionForm([
                            TextInput::make('name')
                                ->required(),
                        ]),
                    TextInput::make('address')
                        ->placeholder('Next avenue no.453')
                        ->autocomplete('address')
                        ->required()
                ]),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'suspended' => 'Suspend',
                    'disabled' => 'Disabled',
                ]),
            FileUpload::make('picture_profile')
                ->avatar()
                ->directory('user-photo')
                ->visibility('private')
                ->previewable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('type', '=', UserDetail::class))
            ->searchable()
            ->searchDebounce(200)
            ->filters([
                SelectFilter::make('status')
                    ->options(['active' => 'active', 'suspended' => 'suspend', 'disabled' => 'disabled']),
            ])
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('status')->searchable(),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
