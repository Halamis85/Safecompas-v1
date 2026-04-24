<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function index(): JsonResponse
    {
        $contacts = Contact::orderBy('type')->orderBy('name')->get();
        return response()->json($contacts);
    }

    public function show($id): JsonResponse
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json(['error' => 'Kontakt nenalezen'], 404);
        }

        return response()->json($contact);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'type'      => 'required|in:supplier,customer,user',
            'is_active' => 'sometimes|boolean',
        ]);

        $contact = Contact::create($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kontakt byl vytvořen',
            'contact' => $contact,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json(['error' => 'Kontakt nenalezen'], 404);
        }

        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'email'     => 'sometimes|email|max:255',
            'type'      => 'sometimes|in:supplier,customer,user',
            'is_active' => 'sometimes|boolean',
        ]);

        $contact->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kontakt byl aktualizován',
            'contact' => $contact->fresh(),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json(['error' => 'Kontakt nenalezen'], 404);
        }

        $contact->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Kontakt byl smazán',
        ]);
    }
}