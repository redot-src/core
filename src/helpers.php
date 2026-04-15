<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Redot\Models\Setting;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Get the specified setting value.
 */
function setting(?string $key = null, mixed $default = null, bool $fresh = false): mixed
{
    if (is_null($key)) {
        return Setting::all()->pluck('value', 'key')->toArray();
    }

    return Setting::get($key, $default, $fresh);
}

/**
 * Get the application name.
 */
function app_name(): string
{
    return Arr::get(setting('app_name'), app()->getLocale()) ?: config('app.name');
}

/**
 * Get the application url.
 */
function app_url(): string
{
    return URL::to('/');
}

/**
 * Get the route from the url.
 */
function route_from_url(string $url): ?string
{
    try {
        $request = Request::create($url);
        $matchedRoute = Route::getRoutes()->match($request);

        return $matchedRoute->getName() ?: null;
    } catch (Throwable) {
        return null;
    }
}

/**
 * Check if the gate allows the given permission.
 */
function route_allowed(string $route, string $guard = 'admins'): bool
{
    if (! auth($guard)->check()) {
        return false;
    }

    $user = auth($guard)->user();
    $key = sprintf('permission.%s.%s.%s', $guard, $user->id, $route);

    return cache()->rememberForever($key, function () use ($route) {
        return Permission::whereName($route)->doesntExist() || Gate::allows($route);
    });
}

/**
 * Check if the url is allowed.
 */
function url_allowed(string $url, string $guard = 'admins'): bool
{
    // Early return if the url is external
    if (! str_contains($url, str_replace(['http://', 'https://'], '', app_url()))) {
        return true;
    }

    return route_allowed(route_from_url($url) ?: '', $guard);
}

function throw_api_exception(Throwable $e): JsonResponse
{
    $code = match (true) {
        $e instanceof HttpException => $e->getStatusCode(),
        $e instanceof ModelNotFoundException => 404,
        $e instanceof ValidationException => 422,
        $e instanceof AuthenticationException => 401,
        $e instanceof AuthorizationException => 403,
        default => 500,
    };

    $message = $e->getMessage() ?: match ($code) {
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        default => 'Something went wrong',
    };

    $payload = match (true) {
        $e instanceof ValidationException => $e->validator->errors()->toArray(),
        config('app.debug') => ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()],
        default => [],
    };

    return response()->json([
        'code' => $code,
        'success' => false,
        'message' => $message,
        'payload' => $payload,
    ], $code);
}

/**
 * Format the given phone number.
 */
function format_phone(string $phone, string $country = 'EG'): string
{
    $instance = PhoneNumberUtil::getInstance();

    return $instance->format($instance->parse($phone, $country), PhoneNumberFormat::E164);
}

/**
 * Trigger the build of the dependencies.
 */
function trigger_dependencies_build(): void
{
    File::deleteDirectories(public_path('assets/dist'));
    File::delete(public_path('assets/dist/lock'));
}

/**
 * Generate a hashed asset path.
 */
function hashed_asset(string $path, ?bool $secure = null): string
{
    $hash = null;
    $realPath = public_path($path);

    if (file_exists($realPath)) {
        $hash = md5(filemtime($realPath));
    }

    return asset($path, $secure) . ($hash ? '?v=' . $hash : '');
}

/**
 * Collect the given array with ellipsis.
 */
function collect_ellipsis($value = [], int $limit = 3, ?string $ellipsis = '...'): Collection
{
    $collection = collect($value);
    $count = $collection->count();

    return $collection
        ->take($limit)
        ->when($count > $limit, function ($collection) use ($count, $limit, $ellipsis) {
            return $collection->push(__($ellipsis, ['count' => $count - $limit]));
        });
}

/**
 * Create a redirect response or redirect to a named route.
 */
function back_or_route(string $route, mixed $parameters = [], bool $absolute = true): string
{
    $url = url()->previous();
    $route = route($route, $parameters, $absolute);
    $appUrl = config('app.url') ?: request()->root();

    if (! $url || $url === url()->current()) {
        return $route;
    }

    if (Str::startsWith($url, $appUrl)) {
        return $url;
    }

    return $route;
}

/**
 * Switch the block.
 */
function switch_badge(mixed $value, ?string $true = null, ?string $false = null): string
{
    $true = $true ?: __('Yes');
    $false = $false ?: __('No');

    return $value ? '<span class="badge bg-success-lt">' . $true . '</span>' : '<span class="badge bg-danger-lt">' . $false . '</span>';
}

/**
 * Render the given component.
 */
function component(string $name, array $data = []): string|View
{
    // Define the base namespace for components
    $baseNamespace = app()->getNamespace() . 'View\\Components\\';

    // Convert the name to a fully qualified class name
    $className = class_exists($name) ? $name : $baseNamespace . str_replace(' ', '\\', ucwords(str_replace('.', ' ', $name)));

    if (! class_exists($className)) {
        // If the class does not exist, render the view as an inline component
        return view("components.$name", $data);
    }

    // Create a new component instance and render it
    return Blade::renderComponent(app()->make($className, $data));
}

/**
 * Search the given model.
 */
function search_model(Builder|QueryBuilder $query, array $columns = [], ?string $term = null): Builder|QueryBuilder
{
    $term = trim($term);

    if (! $term) {
        return $query;
    }

    $handleRelation = function (Builder|QueryBuilder $query, string $column, string $term): void {
        $relation = Str::beforeLast($column, '.');
        $column = Str::afterLast($column, '.');

        if ($query instanceof Builder && method_exists($query->getModel(), $relation)) {
            $query->orWhereHas($relation, function ($query) use ($column, $term) {
                $query->where($column, 'like', "%{$term}%");
            });

            return;
        }

        $query->orWhere($column, 'like', "%{$term}%");
    };

    return $query->where(function ($query) use ($columns, $term, $handleRelation) {
        foreach ($columns as $column) {
            if (str_contains($column, '.')) {
                $handleRelation($query, $column, $term);

                continue;
            }

            $query->orWhere($column, 'like', "%{$term}%");
        }
    });
}

/**
 * Render the no content message.
 */
function no_content(): string
{
    return '<p class="text-muted">' . __('No content') . '</p>';
}

/**
 * Check if the given path is an image.
 */
function is_image(string $path): bool
{
    return str_starts_with(mime_content_type($path), 'image/');
}

/**
 * Create a thumbnail for the given path inside the same directory under "thumbnails" directory.
 */
function create_thumbnail(string $path, int $width = 100, int $height = 100, int $quality = 85): ?string
{
    // Check if file exists
    if (! file_exists($path)) {
        throw new InvalidArgumentException("File does not exist: $path");
    }

    // Check if file is an image
    if (! is_image($path)) {
        throw new InvalidArgumentException("File is not an image: $path");
    }

    // Get file info
    $pathInfo = pathinfo($path);
    $directory = $pathInfo['dirname'];
    $filename = $pathInfo['filename'];
    $extension = strtolower($pathInfo['extension']);

    // Create thumbnails directory if it doesn't exist
    $thumbnailsDir = $directory . DIRECTORY_SEPARATOR . 'thumbnails';
    if (! is_dir($thumbnailsDir)) {
        mkdir($thumbnailsDir, 0755, true);
    }

    // Generate thumbnail filename
    $thumbnailPath = $thumbnailsDir . DIRECTORY_SEPARATOR . $filename . '-thumb.' . $extension;

    // Return existing thumbnail if it exists and is newer than the original
    if (file_exists($thumbnailPath) && filemtime($thumbnailPath) >= filemtime($path)) {
        return str_replace(public_path(), '', $thumbnailPath);
    }

    // Get image dimensions and type
    $imageInfo = getimagesize($path);
    if ($imageInfo === false) {
        throw new InvalidArgumentException("Unable to get image information: $path");
    }

    [$originalWidth, $originalHeight, $imageType] = $imageInfo;

    // Create image resource from file
    $sourceImage = match ($imageType) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($path),
        IMAGETYPE_PNG => imagecreatefrompng($path),
        IMAGETYPE_GIF => imagecreatefromgif($path),
        IMAGETYPE_WEBP => imagecreatefromwebp($path),
        default => throw new InvalidArgumentException("Unsupported image type: $extension")
    };

    if ($sourceImage === false) {
        throw new RuntimeException("Failed to create image resource from: $path");
    }

    // Calculate thumbnail dimensions maintaining aspect ratio
    $aspectRatio = $originalWidth / $originalHeight;

    if ($width / $height > $aspectRatio) {
        $thumbnailWidth = (int) ($height * $aspectRatio);
        $thumbnailHeight = $height;
    } else {
        $thumbnailWidth = $width;
        $thumbnailHeight = (int) ($width / $aspectRatio);
    }

    // Create thumbnail image
    $thumbnailImage = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);

    if ($thumbnailImage === false) {
        throw new RuntimeException('Failed to create thumbnail image resource');
    }

    // Preserve transparency for PNG and GIF
    if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
        imagealphablending($thumbnailImage, false);
        imagesavealpha($thumbnailImage, true);
        $transparent = imagecolorallocatealpha($thumbnailImage, 255, 255, 255, 127);
        imagefill($thumbnailImage, 0, 0, $transparent);
    }

    // Resize image
    if (! imagecopyresampled($thumbnailImage, $sourceImage, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $originalWidth, $originalHeight)) {
        throw new RuntimeException('Failed to resize image');
    }

    // Save thumbnail
    $success = match ($imageType) {
        IMAGETYPE_JPEG => imagejpeg($thumbnailImage, $thumbnailPath, $quality),
        IMAGETYPE_PNG => imagepng($thumbnailImage, $thumbnailPath, 6),
        IMAGETYPE_GIF => imagegif($thumbnailImage, $thumbnailPath),
        IMAGETYPE_WEBP => imagewebp($thumbnailImage, $thumbnailPath, $quality),
        default => false
    };

    if (! $success) {
        throw new RuntimeException("Failed to save thumbnail: $thumbnailPath");
    }

    return str_replace(public_path(), '', $thumbnailPath);
}

/**
 * Parse the given CSV string to an array.
 */
function parse_csv(string|array $csv, ?string $separator = ',', ?callable $callback = null): array
{
    if (is_string($csv)) {
        $csv = explode($separator, $csv);
    }

    return array_filter(array_map($callback ?: 'trim', $csv));
}
