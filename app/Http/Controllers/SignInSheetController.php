<?php

namespace App\Http\Controllers;

use App\Models\ConsentText;
use Illuminate\View\View;

/**
 * Public, printable open house sign-in sheet (marketing page). The consent line is
 * the versioned paper wording from consent_texts with the agent placeholders left
 * as blanks to write in. No wording in the database means no sheet: this page never
 * prints a consent line that is not in the table.
 */
class SignInSheetController extends Controller
{
    public function show(): View
    {
        $paper = ConsentText::paper();
        abort_if($paper === null, 404);

        return view('sheet', ['consent' => $paper->renderBlank()]);
    }
}
