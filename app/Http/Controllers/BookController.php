<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::where('user_id', Auth::id())->get();
        return response()->json([
            'data' => $books,
            'links' => [
                'self' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ],
                'create' => [
                    'href' => url('/api/books'),
                    'method' => 'POST'
                ]
            ]
        ]);
    }

    public function showByUser()
    {
        return $this->index();
    }

    public function show($id)
    {
        if (!$book = Book::find($id)) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        if (Auth::id() !== $book->user_id) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        return response()->json([
            'data' => $book,
            'links' => [
                'self' => [
                    'href' => url("/api/books/{$id}"),
                    'method' => 'GET'
                ],
                'update' => [
                    'href' => url("/api/books/{$id}"),
                    'method' => 'PUT'
                ],
                'delete' => [
                    'href' => url("/api/books/{$id}"),
                    'method' => 'DELETE'
                ],
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ]
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'description' => 'nullable|string',
            'published_year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $book = new Book($validator->validated());
        $book->user_id = Auth::id();
        $book->save();

        return response()->json([
            'data' => $book,
            'links' => [
                'self' => [
                    'href' => url("/api/books/{$book->id}"),
                    'method' => 'GET'
                ],
                'update' => [
                    'href' => url("/api/books/{$book->id}"),
                    'method' => 'PUT'
                ],
                'delete' => [
                    'href' => url("/api/books/{$book->id}"),
                    'method' => 'DELETE'
                ],
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ]
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        if (!Auth::check() || Auth::id() !== $book->user_id) {
            return response()->json(['error' => 'Not authorised'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'published_year' => 'sometimes|required|integer|min:1900|max:'.(date('Y') + 1),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $book->update($validator->validated());

        return response()->json([
            'data' => $book,
            'links' => [
                'self' => [
                    'href' => url("/api/books/{$id}"),
                    'method' => 'GET'
                ],
                'delete' => [
                    'href' => url("/api/books/{$id}"),
                    'method' => 'DELETE'
                ],
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ]
            ]
        ]);
    }

    public function destroy($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['error' => 'Book not found'], 404);
        }

        if (!Auth::check() || Auth::id() !== $book->user_id) {
            return response()->json(['error' => 'Not authorised'], 403);
        }

        $book->delete();

        return response()->json([
            'message' => 'Book deleted successfully',
            'links' => [
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ],
                'create' => [
                    'href' => url('/api/books'),
                    'method' => 'POST'
                ]
            ]
        ]);
    }
}
