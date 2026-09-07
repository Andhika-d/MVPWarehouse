<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\HelpGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminHelpGuideController extends Controller
{
    public function index()
    {
        $guides = HelpGuide::with('creator')->orderBy('sort_order')->orderBy('title')->paginate(20);
        return view('admin.help-guides.index', compact('guides'));
    }

    public function create()
    {
        return view('admin.help-guides.form', ['guide' => new HelpGuide, 'markers' => []]);
    }

    public function store(Request $request)
    {
        $guide = $this->saveGuide($request, new HelpGuide);
        return redirect()->route('admin.help-guides.edit', $guide)->with('success', 'Panduan berhasil disimpan sebagai draft.');
    }

    public function edit(HelpGuide $helpGuide)
    {
        $helpGuide->load('images.markers');
        $markers = $helpGuide->images->first()?->markers->map(fn ($marker) => [
            'x' => $marker->position_x,
            'y' => $marker->position_y,
            'title' => $marker->title,
            'description' => $marker->description,
        ])->values()->all() ?? [];

        return view('admin.help-guides.form', ['guide' => $helpGuide, 'markers' => $markers]);
    }

    public function update(Request $request, HelpGuide $helpGuide)
    {
        $this->saveGuide($request, $helpGuide);
        return back()->with('success', 'Panduan berhasil diperbarui.');
    }

    public function publish(HelpGuide $helpGuide)
    {
        $helpGuide->update(['status' => 'published', 'published_at' => now()]);
        $this->audit('help_guide_published', $helpGuide, 'Mempublikasikan panduan '.$helpGuide->title);
        return back()->with('success', 'Panduan berhasil dipublikasikan.');
    }

    public function archive(HelpGuide $helpGuide)
    {
        $helpGuide->update(['status' => 'archived']);
        $this->audit('help_guide_archived', $helpGuide, 'Mengarsipkan panduan '.$helpGuide->title);
        return back()->with('success', 'Panduan diarsipkan.');
    }

    public function destroy(HelpGuide $helpGuide)
    {
        foreach ($helpGuide->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        $this->audit('help_guide_deleted', $helpGuide, 'Menghapus panduan '.$helpGuide->title);
        $helpGuide->delete();
        return redirect()->route('admin.help-guides.index')->with('success', 'Panduan dihapus.');
    }

    private function saveGuide(Request $request, HelpGuide $guide): HelpGuide
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'audience_role' => ['required', 'in:all,gudang,hr,director,admin'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => [$guide->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'markers' => ['nullable', 'json'],
        ]);

        DB::transaction(function () use ($request, $data, $guide): void {
            $guide->fill([
                'title' => $data['title'],
                'slug' => $guide->slug ?: Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
                'category' => $data['category'],
                'description' => $data['description'] ?? null,
                'audience_role' => $data['audience_role'],
                'sort_order' => $data['sort_order'] ?? 0,
                'created_by' => $guide->created_by ?: auth()->id(),
            ])->save();

            $image = $guide->images()->first();
            if ($request->hasFile('image')) {
                if ($image) Storage::disk('public')->delete($image->image_path);
                $image = $guide->images()->updateOrCreate(['id' => $image?->id], [
                    'image_path' => $request->file('image')->store('help-guides', 'public'),
                    'image_alt' => $guide->title,
                    'sort_order' => 0,
                ]);
            }

            if ($image && ! empty($data['markers'])) {
                $image->markers()->delete();
                foreach (json_decode($data['markers'], true, 512, JSON_THROW_ON_ERROR) as $index => $marker) {
                    $image->markers()->create([
                        'number' => $index + 1,
                        'position_x' => max(0, min(100, (float) ($marker['x'] ?? 0))),
                        'position_y' => max(0, min(100, (float) ($marker['y'] ?? 0))),
                        'title' => $marker['title'] ?? 'Langkah '.($index + 1),
                        'description' => $marker['description'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            } elseif ($image) {
                $image->markers()->delete();
            }
        });

        return $guide;
    }

    private function audit(string $action, HelpGuide $guide, string $details): void
    {
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'target_type' => HelpGuide::class, 'target_id' => $guide->id, 'details' => $details]);
    }
}
