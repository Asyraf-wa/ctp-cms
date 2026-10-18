<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Settings\SocialSettings;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rule;

// PRD AUTHOR-02: the public author profile. Name and email come from Google and are read-only.
class EditProfile extends BaseEditProfile
{
    public function getTitle(): string|Htmlable
    {
        return 'My author profile';
    }

    public function form(Schema $schema): Schema
    {
        /** @var User $user */
        $user = $this->getUser();

        return $schema
            ->inlineLabel(false)
            ->components([
                Section::make('Account')->description('From your Google account.')->columns(2)->schema([
                    TextInput::make('name')->disabled()->dehydrated(false),
                    TextInput::make('email')->disabled()->dehydrated(false),
                ]),
                Section::make('Public profile')->description('Shown on your articles and author page.')->columns(2)->schema([
                    FileUpload::make('avatar_path')->label('Photo')->image()->avatar()->imageEditor()
                        ->disk('public')->directory('avatars')
                        ->helperText('Square image. Falls back to your Google photo.')->columnSpanFull(),
                    TextInput::make('display_name')->label('Display name')->maxLength(100)
                        ->placeholder($user->name),
                    TextInput::make('slug')->label('Profile URL')->required()->maxLength(100)
                        ->prefix(url('/author').'/')
                        ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->rule(Rule::unique('users', 'slug')->ignore($user->getKey()))
                        ->helperText('Changing it redirects the old address automatically.'),
                    TextInput::make('job_title')->label('Job title')->maxLength(100)
                        ->placeholder('Senior Lecturer & Web Developer'),
                    TextInput::make('website_url')->label('Website')->url(),
                    Textarea::make('bio_short')->label('Short bio')->rows(2)->maxLength(300)->columnSpanFull()
                        ->helperText('One or two sentences, shown in the author box under each article.'),
                    RichEditor::make('bio_long')->label('Full bio')->columnSpanFull()
                        ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList']])
                        ->helperText('Shown on your author page. Credentials and experience help search engines trust your tutorials.'),
                ]),
                Section::make('Social profiles')->columns(2)->collapsible()->schema(
                    collect(SocialSettings::NETWORKS)
                        ->map(fn (string $label, string $key) => TextInput::make("socials.{$key}")->label($label)->url()->prefixIcon(Heroicon::OutlinedLink))
                        ->values()
                        ->all(),
                ),
            ]);
    }
}
