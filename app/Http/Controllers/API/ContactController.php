<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function index(): JsonResponse
    {
        $contacts = DB::table('contacts')->get();
        return response()->json($contacts);
    }

    public function show($id): JsonResponse
    {
        $contact = DB::table('contacts')->find($id);

        if (!$contact) {
            return response()->json(['error' => 'Kontakt nenalezen'], 404);
        }

        return response()->json($contact);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string'
        ]);

        $id = DB::table('contacts')->insertGetId([
            'name' => $request->name,
            'email' => $request->email,
            'message' => $request->message,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kontakt vytvořen',
            'id' => $id
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'string',
            'email' => 'email',
            'message' => 'string'
        ]);

        $updated = DB::table('contacts')
            ->where('id', $id)
            ->update(array_merge(
                $request->only(['name', 'email', 'message']),
                ['updated_at' => now()]
            ));

        if (!$updated) {
            return response()->json(['error' => 'Kontakt nenalezen'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kontakt aktualizován'
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $deleted = DB::table('contacts')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['error' => 'Kontakt nenalezen'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kontakt smazán'
        ]);
    }
}
