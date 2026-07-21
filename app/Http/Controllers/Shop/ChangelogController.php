<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ChangelogEntry;

class ChangelogController extends Controller
{
    /** Public release-notes timeline: published entries, newest first. */
    public function index()
    {
        return view('shop.changelog.index', [
            'entries' => ChangelogEntry::published()->timeline()->get(),
        ]);
    }
}
