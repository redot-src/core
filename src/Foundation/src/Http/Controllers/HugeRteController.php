<?php

namespace Redot\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Redot\Traits\CanUploadFile;

class HugeRteController extends Controller
{
    use CanUploadFile;

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image',
        ]);

        $url = $this->uploadFile(request()->file('file'), 'hugerte');

        return response()->json([
            'location' => $url,
        ]);
    }
}
