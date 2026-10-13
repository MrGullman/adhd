<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Privacy\DeleteFamilyRequest;
use App\Jobs\DeleteFamilyJob;
use App\Jobs\ExportFamilyJob;
use App\Models\DataExport;
use App\Models\Picture;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Familjens data: parents download everything or remove the whole family.
 */
class DataController extends Controller
{
    public function index(CurrentFamily $current): Response
    {
        $family = $current->getOrFail();
        Gate::authorize('manageMembers', $family);

        return Inertia::render('family/Data', [
            'familyName' => $family->name,
            'exports' => DataExport::query()
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (DataExport $export) => [
                    'id' => $export->id,
                    'status' => $export->status,
                    'size' => $export->size,
                    'createdAt' => $export->created_at?->toIso8601String(),
                    'expiresAt' => $export->expires_at?->toIso8601String(),
                ]),
        ]);
    }

    public function export(Request $request, CurrentFamily $current): RedirectResponse
    {
        $family = $current->getOrFail();
        Gate::authorize('manageMembers', $family);

        if (! DataExport::query()->where('status', 'pending')->exists()) {
            $export = new DataExport(['requested_by' => $request->user()?->id]);
            $export->save();

            ExportFamilyJob::dispatch($export->id, $family->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Exporten görs i ordning. Det tar en liten stund.']);

        return to_route('family.data');
    }

    public function download(CurrentFamily $current, DataExport $export): StreamedResponse
    {
        Gate::authorize('manageMembers', $current->getOrFail());
        abort_unless($export->status === 'ready' && $export->path !== null && $export->expires_at?->isFuture(), 404);

        return Storage::disk(Picture::DISK)->download($export->path, 'npf-familjen-'.$export->created_at?->format('Y-m-d').'.zip');
    }

    /**
     * Removes the whole family. The parent is logged out right away and the
     * rest happens in the background.
     */
    public function destroy(DeleteFamilyRequest $request, CurrentFamily $current): RedirectResponse
    {
        DeleteFamilyJob::dispatch($current->getOrFail()->id);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
