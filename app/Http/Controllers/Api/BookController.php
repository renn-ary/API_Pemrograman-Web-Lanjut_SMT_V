<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class BookController extends Controller
{
    #[OA\Get(
        path: '/api/books',
        operationId: 'booksIndex',
        summary: 'Daftar buku (paginasi + pencarian)',
        security: [['sanctum' => []]],
        tags: ['Books'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Cari berdasarkan judul atau penulis',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar buku'
            ),
            new OA\Response(
                response: 401,
                description: 'Belum login'
            ),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $search = $request->string('search')->trim()->toString();

        $books = Book::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return BookResource::collection($books);
    }

    #[OA\Post(
        path: '/api/books',
        operationId: 'booksStore',
        summary: 'Tambah buku baru',
        security: [['sanctum' => []]],
        tags: ['Books'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'author', type: 'string'),
                    new OA\Property(property: 'publisher', type: 'string'),
                    new OA\Property(property: 'isbn', type: 'string'),
                    new OA\Property(property: 'published_year', type: 'integer'),
                    new OA\Property(property: 'stock', type: 'integer'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Buku berhasil dibuat'),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create($request->validated());

        return (new BookResource($book))->response()->setStatusCode(201);
    }

    public function show(Book $book): BookResource
    {
        return new BookResource($book);
    }

    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $book->update($request->validated());

        return new BookResource($book);
    }

    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json([
            'message' => 'Buku berhasil dihapus',
        ]);
    }
}
