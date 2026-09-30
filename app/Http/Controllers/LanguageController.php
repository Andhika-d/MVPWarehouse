<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('language.supported')))],
        ]);

        $locale = $data['locale'];

        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
