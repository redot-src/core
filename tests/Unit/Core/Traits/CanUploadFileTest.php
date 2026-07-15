<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Redot\Traits\CanUploadFile;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Spatie\LaravelImageOptimizer\ImageOptimizerServiceProvider;

beforeEach(function () {
    $this->uploader = new class
    {
        use CanUploadFile;
    };

    File::deleteDirectory(public_path('uploads/can-upload-file-test'));
});

afterEach(function () {
    File::deleteDirectory(public_path('uploads/can-upload-file-test'));
    File::delete(public_path('outside.txt'));
    File::delete(dirname(public_path()) . '/outside.txt');
});

it('uses the server detected extension instead of the client extension', function () {
    $file = UploadedFile::fake()->create('avatar.php', 1, 'image/jpeg');

    $url = $this->uploader->uploadFile($file, 'can-upload-file-test');
    $path = parse_url($url, PHP_URL_PATH);

    expect($path)->toEndWith('.jpg')
        ->and(File::exists(public_path($path)))->toBeTrue();
});

it('rejects executable and active web content', function (string $name, string $mime) {
    $file = UploadedFile::fake()->create($name, 1, $mime);

    expect(fn () => $this->uploader->uploadFile($file, 'can-upload-file-test'))
        ->toThrow(ValidationException::class);
})->with([
    ['shell.php', 'application/x-php'],
    ['payload.html', 'text/html'],
    ['graphic.svg', 'image/svg+xml'],
]);

it('uses the configured allowed extensions', function () {
    config()->set('redot.uploads.allowed_extensions', ['json']);
    $file = UploadedFile::fake()->create('data.json', 1, 'application/json');

    $url = $this->uploader->uploadFile($file, 'can-upload-file-test');
    $path = parse_url($url, PHP_URL_PATH);

    expect($path)->toEndWith('.json')
        ->and(File::exists(public_path($path)))->toBeTrue();
});

it('orients and optimizes image uploads when requested', function () {
    app()->register(ImageOptimizerServiceProvider::class);
    ImageOptimizer::shouldReceive('optimize')->once();
    $file = UploadedFile::fake()->image('avatar.jpg', 40, 20);

    $url = $this->uploader->uploadFile($file, 'can-upload-file-test', optimize: true);
    $path = public_path(parse_url($url, PHP_URL_PATH));

    expect(File::exists($path))->toBeTrue()
        ->and(getimagesize($path))->toMatchArray([40, 20]);
});

it('rejects upload paths outside the uploads directory', function () {
    $file = UploadedFile::fake()->create('avatar.jpg', 1, 'image/jpeg');

    expect(fn () => $this->uploader->uploadFile($file, '../outside'))
        ->toThrow(InvalidArgumentException::class);
});

it('deletes files only from local upload urls', function () {
    $directory = public_path('uploads/can-upload-file-test');
    File::ensureDirectoryExists($directory);
    File::put($directory . '/safe.txt', 'safe');
    File::put(public_path('outside.txt'), 'outside');

    expect($this->uploader->deleteFileFromUrl(url('/uploads/can-upload-file-test/safe.txt')))->toBeTrue()
        ->and(File::exists($directory . '/safe.txt'))->toBeFalse()
        ->and($this->uploader->deleteFileFromUrl(url('/uploads/%2e%2e/outside.txt')))->toBeFalse()
        ->and($this->uploader->deleteFileFromUrl('https://example.com/uploads/outside.txt'))->toBeFalse()
        ->and(File::exists(public_path('outside.txt')))->toBeTrue();
});

it('does not delete paths outside the public directory', function () {
    $outsidePath = dirname(public_path()) . '/outside.txt';
    File::put($outsidePath, 'outside');

    expect($this->uploader->deleteFile('../outside.txt'))->toBeFalse()
        ->and(File::exists($outsidePath))->toBeTrue();
});
