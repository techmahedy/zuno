<?php

namespace Zuno\Support;

class StringService
{
    /**
     * Extract a substring from the given input string.
     *
     * This method utilizes `mb_substr` to safely handle multi-byte characters
     * while extracting a portion of the string.
     *
     * @param string   $input  The original string from which to extract the substring.
     * @param int      $start  The starting position (0-based index).
     *                         - A positive value starts from the beginning.
     *                         - A negative value starts from the end of the string.
     * @param int|null $length (Optional) The length of the substring.
     *                         - If omitted, the substring extends to the end of the string.
     *                         - A negative length excludes the specified number of characters from the end.
     * @return string The extracted substring.
     *
     * @example
     * Extracts from position 7 to the end
     * Str::substr("Hello, World!", 7); 
     * Output: "World!"
     */
    public function substr(string $input, int $start, ?int $length = null): string
    {
        return mb_substr($input, $start, $length, 'UTF-8');
    }

    /**
     * Compute the length of the given string.
     *
     * @param string $input
     *
     * @return int
     */
    public function len(string $input): int
    {
        return mb_strlen($input, 'UTF-8');
    }

    /**
     * Count the number of words in a string.
     *
     * @param string $string The input string.
     * @return int The word count.
     */
    public function count_word(string $string): int
    {
        return str_word_count($string);
    }

    /**
     * Check if a given string is a palindrome.
     *
     * @param string $string The input string.
     * @return bool Returns true if the string is a palindrome, false otherwise.
     */
    public function is_palindrome(string $string): bool
    {
        $cleaned = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $string));

        return $cleaned === strrev($cleaned);
    }

    /**
     * Generate a random alphanumeric string of a given length.
     *
     * @param int $length The length of the random string (default: 10).
     * @return string The generated random string.
     */
    public function random(int $length = 10): string
    {
        return substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $length)), 0, $length);
    }

    /**
     * Convert a snake_case string to camelCase.
     *
     * @param string $input The snake_case string.
     * @return string The converted camelCase string.
     */
    public function camel(string $input): string
    {
        return lcfirst(str_replace('_', '', ucwords($input, '_')));
    }

    /**
     * Mask a string with a specified number of visible characters at the start and end.
     *
     * @param string $string The string to mask
     * @param int $visibleFromStart Number of visible characters from the start of the string
     * @param int $visibleFromEnd Number of visible characters from the end of the string
     * @param string $maskCharacter The character used to mask the string
     *
     * @return string The masked string
     */
    public function mask(
        string $string,
        int $visibleFromStart = 1,
        int $visibleFromEnd = 1,
        string $maskCharacter = '*'
    ): string {
        $length = strlen($string);
        $maskedLength = $length - $visibleFromEnd;
        $startPart = substr($string, 0, $visibleFromStart);
        $endPart = substr($string, -$visibleFromEnd);

        return str_pad($startPart, $maskedLength, $maskCharacter, STR_PAD_RIGHT) . $endPart;
    }

    /**
     * Truncate a string to a specific length and append a suffix if truncated.
     *
     * @param string $string The input string.
     * @param int $maxLength The maximum allowed length.
     * @param string $suffix The suffix to append if truncated (default: '...').
     * @return string The truncated string.
     */
    public function truncate(string $string, int $maxLength, string $suffix = '...'): string
    {
        return (strlen($string) > $maxLength) ? substr($string, 0, $maxLength) . $suffix : $string;
    }

    /**
     * Convert a camelCase string to snake_case.
     *
     * @param string $input The camelCase string.
     * @return string The converted snake_case string.
     */
    public function snake(string $input): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }

    /**
     * Convert a string to title case (each word capitalized).
     *
     * @param string $input The input string.
     * @return string The title-cased string.
     *
     * @example
     * Str::title("hello world"); // Returns "Hello World"
     */
    public function title(string $input): string
    {
        return mb_convert_case($input, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Generate a URL-friendly slug from a string.
     *
     * @param string $input The input string.
     * @param string $separator The word separator (default: '-').
     * @return string The generated slug.
     *
     * @example
     * Str::slug("Hello World!"); // Returns "hello-world"
     */
    public function slug(string $input, string $separator = '-'): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', $separator, strtolower($input));
        return trim($slug, $separator);
    }

    /**
     * Check if a string contains another string (case-insensitive).
     *
     * @param string $haystack The string to search in.
     * @param string $needle The string to search for.
     * @return bool True if found, false otherwise.
     *
     * @example
     * Str::contains("Hello World", "world"); // Returns true
     */
    public function contains(string $haystack, string $needle): bool
    {
        return mb_stripos($haystack, $needle, 0, 'UTF-8') !== false;
    }

    /**
     * Limit the number of words in a string.
     *
     * @param string $string The input string.
     * @param int $words The maximum number of words.
     * @param string $end The ending suffix (default: '...').
     * @return string The truncated string.
     *
     * @example
     * Str::limitWords("This is a test string", 3); // Returns "This is a..."
     */
    public function limit_words(string $string, int $words, string $end = '...'): string
    {
        $wordArray = explode(' ', $string);
        if (count($wordArray) <= $words) {
            return $string;
        }
        return implode(' ', array_slice($wordArray, 0, $words)) . $end;
    }

    /**
     * Remove all whitespace from a string.
     *
     * @param string $input The input string.
     * @return string The string without whitespace.
     *
     * @example
     * Str::removeWhitespace("Hello   World"); // Returns "HelloWorld"
     */
    public function remove_white_space(string $input): string
    {
        return preg_replace('/\s+/', '', $input);
    }

    /**
     * Generate a UUID v4 string.
     *
     * @return string The generated UUID.
     *
     * @example
     * Str::uuid(); // Returns something like "f47ac10b-58cc-4372-a567-0e02b2c3d479"
     */
    public function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Check if a string starts with another string (case-sensitive).
     *
     * @param string $haystack The string to search in.
     * @param string $needle The string to search for.
     * @return bool True if found, false otherwise.
     *
     * @example
     * Str::startsWith("Hello World", "Hello"); // Returns true
     */
    public function starts_with(string $haystack, string $needle): bool
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }

    /**
     * Check if a string ends with another string (case-sensitive).
     *
     * @param string $haystack The string to search in.
     * @param string $needle The string to search for.
     * @return bool True if found, false otherwise.
     *
     * @example
     * Str::endsWith("Hello World", "World"); // Returns true
     */
    public function ends_with(string $haystack, string $needle): bool
    {
        if (strlen($needle) === 0) {
            return true;
        }
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Convert a string to studly case (StudlyCase).
     *
     * @param string $input The input string.
     * @return string The studly-cased string.
     *
     * @example
     * Str::studly("hello_world"); // Returns "HelloWorld"
     */
    public function studly(string $input): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $input)));
    }

    /**
     * Reverse a string while preserving multi-byte characters.
     *
     * @param string $input The input string.
     * @return string The reversed string.
     */
    public function reverse(string $input): string
    {
        return implode('', array_reverse(mb_str_split($input)));
    }

    /**
     * Extract all numeric digits from a string.
     *
     * @param string $input The input string.
     * @return string A string containing only numeric digits.
     */
    public function extract_numbers(string $input): string
    {
        return preg_replace('/\D/', '', $input);
    }

    /**
     * Find the longest common substring between two strings.
     *
     * @param string $str1 The first string.
     * @param string $str2 The second string.
     * @return string The longest common substring.
     */
    public function longest_common_substring(string $str1, string $str2): string
    {
        $matrix = array_fill(0, strlen($str1) + 1, array_fill(0, strlen($str2) + 1, 0));
        $maxLength = 0;
        $endIndex = 0;

        for ($i = 1; $i <= strlen($str1); $i++) {
            for ($j = 1; $j <= strlen($str2); $j++) {
                if ($str1[$i - 1] === $str2[$j - 1]) {
                    $matrix[$i][$j] = $matrix[$i - 1][$j - 1] + 1;
                    if ($matrix[$i][$j] > $maxLength) {
                        $maxLength = $matrix[$i][$j];
                        $endIndex = $i;
                    }
                }
            }
        }

        return substr($str1, $endIndex - $maxLength, $maxLength);
    }

    /**
     * Convert a string to leetspeak (1337).
     *
     * @param string $input The input string.
     * @return string The converted leetspeak string.
     */
    public function leet_speak(string $input): string
    {
        $map = [
            'a' => '4',
            'e' => '3',
            'i' => '1',
            'o' => '0',
            's' => '5',
            't' => '7'
        ];

        return strtr(strtolower($input), $map);
    }

    /**
     * Extract all email addresses from a string.
     *
     * @param string $input The input string.
     * @return array An array of extracted email addresses.
     */
    public function extract_emails(string $input): array
    {
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $input, $matches);

        return $matches[0] ?? [];
    }

    /**
     * Highlight all occurrences of a keyword in a string using HTML tags.
     *
     * @param string $input The input string.
     * @param string $keyword The keyword to highlight.
     * @param string $tag The HTML tag to wrap the keyword in (default: <strong>).
     * @return string The modified string with highlighted keywords.
     */
    public function highlight_keyword(string $input, string $keyword, string $tag = 'strong'): string
    {
        if (empty($keyword)) {
            return $input;
        }

        return preg_replace(
            "/(" . preg_quote($keyword, '/') . ")/i",
            "<$tag>$1</$tag>",
            $input
        );
    }
}
