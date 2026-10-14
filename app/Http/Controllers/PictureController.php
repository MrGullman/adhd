<?php

namespace App\Http\Controllers;

use App\Actions\Pictures\StorePicture;
use App\Enums\PicturePurpose;
use App\Http\Requests\Pictures\StorePictureRequest;
use App\Models\Picture;
use App\Models\PictureCategory;
use App\Support\CurrentFamily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PictureController extends Controller
{
    /**
     * Pictures to choose from (the picture picker): the gallery and the
     * family's own photos, filtered by search text, category or "egna".
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->string('sok')->toString());
        $category = $request->string('kategori')->toString();
        // "mat" when choosing a picture for a recipe: the family's food photos.
        $purpose = $request->string('till')->toString() === 'mat' ? PicturePurpose::Food : PicturePurpose::Support;

        $pictures = Picture::query()
            ->where(fn ($query) => $query->whereNull('family_id')->orWhere('purpose', $purpose))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('label', 'like', "%{$search}%")
                ->orWhere('keywords', 'like', "%{$search}%")))
            ->when($category === 'egna', fn ($query) => $query->whereNotNull('family_id'))
            ->when(ctype_digit($category), fn ($query) => $query->where('picture_category_id', (int) $category))
            ->orderBy('label')
            ->limit(120)
            ->get();

        return response()->json([
            'pictures' => $pictures->map(fn (Picture $picture) => $picture->toPicker()),
            'categories' => PictureCategory::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * The picture file. The family's photos are only found within the family
     * (SharedOrFamilyScope), so other families get a 404.
     */
    public function show(Request $request, CurrentFamily $current, Picture $picture): StreamedResponse
    {
        $user = $request->user();

        if ($user !== null) {
            Gate::authorize('view', $picture);
        } elseif ($current->device() === null) {
            abort(404);
        }

        return Storage::disk(Picture::DISK)->response($picture->path, null, [
            // The URL has a version, so the browser may keep the file.
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }

    /**
     * The family uploads its own photo, e.g. from the phone's camera.
     */
    public function store(StorePictureRequest $request, CurrentFamily $current, StorePicture $store): JsonResponse
    {
        $picture = $store->upload(
            $request->file('photo'),
            $request->string('label')->toString(),
            $current->getOrFail(),
            $request->user(),
            purpose: $request->enum('purpose', PicturePurpose::class) ?? PicturePurpose::Support,
        );

        return response()->json(['picture' => $picture->refresh()->toPicker()], 201);
    }
}
