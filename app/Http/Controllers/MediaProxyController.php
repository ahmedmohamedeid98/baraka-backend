<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MediaProxyController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $path)
    {
        // ✅ Clean path to avoid traversal
        $path = ltrim(str_replace(['..', '\\'], '', $path), '/');

        // ✅ Allow only requests from *.istoria.app
        // $origin = $request->header('Origin');

        // if (!app()->environment('local')) {
        //     if (!$origin || !preg_match('#^https://([a-z0-9-]+\.)?istoria\.app$#i', $origin)) {
        //         abort(403, 'Forbidden: Unauthorized access.');
        //     }
        // }


        /** @var Illuminate\Contracts\Filesystem\Cloud $disk */
        $disk = Storage::disk('r2');

        // ✅ Cache file existence for 10 minutes
        $exists = Cache::remember("file_exists:{$path}", 600, fn() => $disk->exists($path) ?: null);
        if (!$exists) {
            abort(404, 'File not found.');
        }

        // ✅ Cache MIME type separately for efficiency
        $mimeType = Cache::remember("file_mime:{$path}", 600, fn() => $disk->mimeType($path));

        // ✅ Return file with CORS + caching headers
        return response($disk->get($path), 200)
            ->header('Content-Type', $mimeType)
            // ->header('Access-Control-Allow-Origin', '')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('X-Served-By', 'iStoria Proxy');
    }
}
