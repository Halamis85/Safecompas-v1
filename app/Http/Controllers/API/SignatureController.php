<?php
// Autorizovaný endpoint pro načtení podpisu

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SignatureController extends Controller
{

    public function show(string $filename): StreamedResponse|\Illuminate\Http\Response
    {
        // Whitelist: jen UUID-like jména s .png příponou (zabrání directory traversal)
        if (!preg_match('/^[A-Za-z0-9._-]+\.png$/', $filename)) {
            abort(404);
        }

        $path = 'signatures/' . $filename;
        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        // Cache hlavička – stejný UUID dává stejný obrázek navždy.
        return Storage::disk('local')->response($path, $filename, [
            'Cache-Control'        => 'private, max-age=2592000',  // 30 dní jen pro přihlášeného
            'Content-Type'         => 'image/png',
            'Content-Disposition'  => 'inline; filename="' . $filename . '"',
        ]);
    }
}
