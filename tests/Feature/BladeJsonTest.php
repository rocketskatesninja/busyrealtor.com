<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Blade's @json directive splits its expression on commas: the first part is the value,
 * the second the options, the third the depth, and anything after that is dropped. So
 * @json(['a' => f($x), 'b' => g($y)], FLAGS) does not mean what it reads as — the array
 * is cut short or the flags are silently discarded.
 *
 * Two views here compiled to json_encode([...]) with no flags at all, which is how a
 * single-quoted data attribute loses the escaping that keeps an apostrophe in the data
 * from ending the attribute early. A third produced an unbalanced array and a 500.
 *
 * Build the array in @php and pass the variable.
 */
class BladeJsonTest extends TestCase
{
    public function test_no_view_passes_a_literal_array_to_the_json_directive(): void
    {
        $offenders = [];

        foreach ($this->views() as $file) {
            preg_match_all('/@json\(\s*\[/', file_get_contents($file), $m);

            if ($m[0]) {
                $offenders[] = str_replace(resource_path('views').'/', '', $file).' ('.count($m[0]).')';
            }
        }

        $this->assertSame([], $offenders,
            "@json() splits on commas, so a literal array loses its flags or its tail:\n  "
            .implode("\n  ", $offenders));
    }

    /** Every @json call that passes flags must be passing a variable, not an expression with commas. */
    public function test_every_json_directive_with_flags_passes_a_single_variable(): void
    {
        $offenders = [];

        foreach ($this->views() as $file) {
            preg_match_all('/@json\((.+?)\)\s*[\'"]/', file_get_contents($file), $m);

            foreach ($m[1] as $expression) {
                if (! str_contains($expression, 'JSON_')) {
                    continue;
                }
                // Everything before the first comma is the value; it has to be one variable.
                $value = trim(explode(',', $expression)[0]);
                if (! preg_match('/^\$[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
                    $offenders[] = str_replace(resource_path('views').'/', '', $file).": {$value}";
                }
            }
        }

        $this->assertSame([], $offenders,
            "these would lose their JSON_* flags to the comma split:\n  ".implode("\n  ", $offenders));
    }

    private function views(): array
    {
        return glob(resource_path('views').'/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE);
    }
}
