<?php

namespace App\Filament\Resources\RoutineTemplates;

use App\Enums\DayPart;
use App\Enums\StepType;
use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Resources\RoutineTemplates\Pages\CreateRoutineTemplate;
use App\Filament\Resources\RoutineTemplates\Pages\EditRoutineTemplate;
use App\Filament\Resources\RoutineTemplates\Pages\ListRoutineTemplates;
use App\Models\Picture;
use App\Models\Routine;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Ready-made routines that families copy and adapt. Never family data.
 */
class RoutineTemplateResource extends Resource
{
    use AuthorizesAdmins;

    protected static ?string $model = Routine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Rutiner';

    protected static ?string $navigationLabel = 'Rutinmallar';

    protected static ?string $modelLabel = 'rutinmall';

    protected static ?string $pluralModelLabel = 'rutinmallar';

    protected static ?string $slug = 'rutinmallar';

    /**
     * @return Builder<Routine>
     */
    public static function getEloquentQuery(): Builder
    {
        return Routine::templates();
    }

    public static function form(Schema $schema): Schema
    {
        $dayParts = collect(DayPart::cases())->mapWithKeys(fn (DayPart $part) => [$part->value => $part->label()])->all();
        $pictures = fn (?string $search): array => Picture::query()
            ->whereNull('family_id')
            ->when($search, fn ($query) => $query->where('label', 'like', "%{$search}%"))
            ->orderBy('label')
            ->limit(50)
            ->pluck('label', 'id')
            ->all();

        return $schema->components([
            TextInput::make('name')->label('Namn')->required()->maxLength(50),
            Select::make('day_part')->label('Del av dagen')->options($dayParts),
            TimePicker::make('done_by')->label('Klar senast')->seconds(false),
            Toggle::make('ordered')->label('Ett steg i taget (fast ordning)')->default(true),
            Repeater::make('steps')
                ->label('Steg')
                ->relationship('steps')
                ->orderColumn('sort_order')
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('label')->label('Text')->required()->maxLength(50)->columnSpan(2),
                    TextInput::make('minutes')->label('Minuter')->integer()->minValue(1)->maxValue(180),
                    Select::make('picture_id')
                        ->label('Bild')
                        ->searchable()
                        ->getSearchResultsUsing($pictures)
                        ->getOptionLabelUsing(fn ($value): ?string => Picture::query()->whereKey($value)->value('label')),
                    Select::make('type')
                        ->label('Typ')
                        ->options([StepType::Step->value => 'Vanligt steg', StepType::Packlist->value => 'Kolla packningen'])
                        ->default(StepType::Step->value)
                        ->required(),
                    Toggle::make('optional')->label('Valfritt'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Namn')->searchable(),
                TextColumn::make('steps_count')->label('Steg')->counts('steps'),
                TextColumn::make('done_by')->label('Klar senast')->time('H:i'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoutineTemplates::route('/'),
            'create' => CreateRoutineTemplate::route('/create'),
            'edit' => EditRoutineTemplate::route('/{record}/edit'),
        ];
    }
}
