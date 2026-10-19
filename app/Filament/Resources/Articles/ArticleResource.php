<?php

namespace App\Filament\Resources\Articles;

use App\Enums\ArticleKind;
use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Models\Article;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Om NPF: the public knowledge library. Topics on the start page, situations
 * in "Känner du igen dig?" and the welcome text, all in Markdown.
 */
class ArticleResource extends Resource
{
    use AuthorizesAdmins;

    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Om NPF';

    protected static ?string $navigationLabel = 'Kunskapsbibliotek';

    protected static ?string $modelLabel = 'artikel';

    protected static ?string $pluralModelLabel = 'artiklar';

    public static function form(Schema $schema): Schema
    {
        $isSituation = fn (Get $get) => $get('kind') === ArticleKind::Situation->value || $get('kind') === ArticleKind::Situation;

        return $schema->components([
            Select::make('kind')
                ->label('Typ')
                ->options(collect(ArticleKind::cases())->mapWithKeys(fn (ArticleKind $kind) => [$kind->value => $kind->label()]))
                ->default(ArticleKind::Topic->value)
                ->required()
                ->live(),
            TextInput::make('title')
                ->label('Rubrik')
                ->required()
                ->maxLength(120)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => filled($get('slug')) ? null : $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')
                ->label('Adress')
                ->helperText('Syns i länken: npf-familjen…/om-npf/adress. Bara a–z, 0–9 och bindestreck.')
                ->required()
                ->maxLength(80)
                ->regex('/^[a-z0-9-]+$/')
                ->unique(ignoreRecord: true),
            Textarea::make('summary')
                ->label('Kort beskrivning')
                ->helperText('Syns på kortet och under rubriken.')
                ->maxLength(255)
                ->rows(2),
            Select::make('icon')
                ->label('Ikon på kortet')
                ->options(array_combine(Article::ICONS, Article::ICONS)),
            MarkdownEditor::make('body')
                ->label('Text')
                ->helperText('## blir en underrubrik, **fet**, - punktlista, [text](länk).')
                ->hidden($isSituation)
                ->columnSpanFull(),
            MarkdownEditor::make('why')
                ->label('Varför kan detta hända?')
                ->visible($isSituation)
                ->columnSpanFull(),
            MarkdownEditor::make('helps')
                ->label('Vad kan hjälpa?')
                ->visible($isSituation)
                ->columnSpanFull(),
            MarkdownEditor::make('in_app')
                ->label('Så kan du använda NPF Familjen')
                ->visible($isSituation)
                ->columnSpanFull(),
            TextInput::make('sort_order')->label('Ordning')->helperText('Lägst först.')->integer()->minValue(0)->default(0)->required(),
            Toggle::make('published')->label('Publicerad')->helperText('Syns på de öppna sidorna.')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Rubrik')->searchable(),
                TextColumn::make('kind')->label('Typ')->badge()->formatStateUsing(fn (ArticleKind $state) => $state->label()),
                IconColumn::make('published')->label('Publicerad')->boolean(),
                TextColumn::make('sort_order')->label('Ordning')->numeric()->sortable(),
                TextColumn::make('updated_at')->label('Ändrad')->since(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('kind')->label('Typ')->options(collect(ArticleKind::cases())->mapWithKeys(fn (ArticleKind $kind) => [$kind->value => $kind->label()])),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Visa')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Article $record) => $record->kind === ArticleKind::Intro ? route('info.index') : route('info.show', $record->slug))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
