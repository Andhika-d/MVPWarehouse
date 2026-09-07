<?php

namespace App\Http\Controllers;

use App\Models\HelpGuide;
use Illuminate\Http\Request;

class HelpGuideController extends Controller
{
    public function index()
    {
        $guides = HelpGuide::with(['images.markers'])
            ->visibleTo(auth()->user()->role)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('help.index', compact('guides'));
    }

    public function show(HelpGuide $guide)
    {
        abort_unless($guide->status === 'published' && in_array($guide->audience_role, ['all', auth()->user()->role], true), 404);

        $guide->load('images.markers');

        return view('help.show', compact('guide'));
    }
}
