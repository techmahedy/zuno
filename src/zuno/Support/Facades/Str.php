<?php

namespace Zuno\Support\Facades;

/**
 * @method static \Zuno\Support\StringService substr(string $input, int $start, ?int $length = null): string
 * @method static \Zuno\Support\StringService len(string $input): int
 * @method static \Zuno\Support\StringService count_word(string $string): int
 * @method static \Zuno\Support\StringService is_palindrome(string $string): bool
 * @method static \Zuno\Support\StringService random(int $length = 10): string
 * @method static \Zuno\Support\StringService camel(string $input): string
 * @method static \Zuno\Support\StringService mask(string $string,int $visibleFromStart = 1,int $visibleFromEnd = 1,string $maskCharacter = '*' ): string
 * @method static \Zuno\Support\StringService truncate(string $string, int $maxLength, string $suffix = '...'): string
 * @method static \Zuno\Support\StringService snake(string $input): string
 * @method static \Zuno\Support\StringService title(string $input): string
 * @method static \Zuno\Support\StringService slug(string $input, string $separator = '-'): string
 * @method static \Zuno\Support\StringService contains(string $haystack, string $needle): bool
 * @method static \Zuno\Support\StringService limit_words(string $string, int $words, string $end = '...'): string
 * @method static \Zuno\Support\StringService remove_white_space(string $input): string
 * @method static \Zuno\Support\StringService uuid(): string
 * @method static \Zuno\Support\StringService starts_with(string $haystack, string $needle): bool
 * @method static \Zuno\Support\StringService ends_with(string $haystack, string $needle): bool
 * @method static \Zuno\Support\StringService studly(string $input): string
 * @method static \Zuno\Support\StringService reverse(string $input): string
 * @method static \Zuno\Support\StringService extract_numbers(string $input): string
 * @method static \Zuno\Support\StringService longest_common_Substring(string $str1, string $str2): string
 * @method static \Zuno\Support\StringService leet_speak(string $input): string
 * @method static \Zuno\Support\StringService extract_emails(string $input): array
 * @method static \Zuno\Support\StringService highlight_keyword(string $input, string $keyword, string $tag = 'strong'): string
 * @see \Zuno\Support\StringService
 */

use Zuno\Facade\BaseFacade;

class Str extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'str';
    }
}
