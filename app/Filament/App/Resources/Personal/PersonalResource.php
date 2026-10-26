<?php

namespace App\Filament\App\Resources\Personal;

use App\Filament\App\Resources\Personal\Pages\CreatePersonal;
use App\Filament\App\Resources\Personal\Pages\EditPersonal;
use App\Filament\App\Resources\Personal\Pages\ListPersonal;
use App\Filament\App\Resources\Personal\Schemas\PersonalForm;
use App\Filament\App\Resources\Personal\Tables\PersonalTable;
use App\Models\PnfUser;
use App\Models\User;
use App\Support\PnfActual;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Personal del PNF (coordinadores, docentes y administrativos), gestionado
 * por el coordinador y el administrativo. El rol de cada uno en este PNF
 * llega en la columna "rol_en_pnf".
 */
class PersonalResource extends Resource
{
    protected static ?string $model = User::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $slug = 'personal';

    protected static ?string $modelLabel = 'miembro del personal';

    protected static ?string $pluralModelLabel = 'personal';

    protected static ?string $navigationLabel = 'Personal';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Académico';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getEloquentQuery(): Builder
    {
        $pnfId = app(PnfActual::class)->id();

        return parent::getEloquentQuery()
            ->whereHas('pnfs', fn (Builder $pnfs) => $pnfs->whereKey($pnfId))
            ->select('users.*')
            ->addSelect(['rol_en_pnf' => PnfUser::query()
                ->select('rol')
                ->whereColumn('pnf_user.user_id', 'users.id')
                ->where('pnf_user.pnf_id', $pnfId)]);
    }

    public static function form(Schema $schema): Schema
    {
        return PersonalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonalTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonal::route('/'),
            'create' => CreatePersonal::route('/create'),
            'edit' => EditPersonal::route('/{record}/edit'),
        ];
    }
}
