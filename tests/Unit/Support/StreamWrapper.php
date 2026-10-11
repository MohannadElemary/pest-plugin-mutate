<?php

declare(strict_types=1);

use Pest\Mutate\Support\StreamWrapper;

beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().'/pest-mutate-stream-wrapper-'.bin2hex(random_bytes(4));
    mkdir($this->directory);

    $this->original = $this->directory.'/original.php';
    $this->override = $this->directory.'/override.php';

    file_put_contents($this->original, "<?php\n");
    file_put_contents($this->override, "<?php\n");
});

afterEach(function (): void {
    StreamWrapper::disable();

    foreach (glob($this->directory.'/*') ?: [] as $file) {
        chmod($file, 0o644);
        unlink($file);
    }

    rmdir($this->directory);
});

it('reports a file that cannot be read as existing', function (): void {
    $locked = $this->directory.'/locked.log';

    file_put_contents($locked, 'content');
    chmod($locked, 0o000);

    StreamWrapper::start($this->original, $this->override);

    clearstatcache(true, $locked);

    expect(file_exists($locked))->toBeTrue()
        ->and(is_file($locked))->toBeTrue()
        ->and(filesize($locked))->toBe(7);
})->skip(
    fn (): bool => function_exists('posix_geteuid') && posix_geteuid() === 0,
    'The root user ignores file permissions.',
);

it('reports a missing file as missing', function (): void {
    StreamWrapper::start($this->original, $this->override);

    $missing = $this->directory.'/missing.log';

    expect(file_exists($missing))->toBeFalse()
        ->and(is_file($missing))->toBeFalse()
        ->and(file_exists($missing.'/child'))->toBeFalse();
});
