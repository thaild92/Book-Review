<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $title = $request->input('title');
        $filter = $request->input('filter', '');


        $books = Book::when(
            $title,
            fn($query, $title) => $query->title($title)
        );

        $books = match ($filter) {
            'popular_last_month' => $books->popularLastMonth(),
            'popular_last_6months' => $books->popularLast6Months(),
            'highest_rated_last_month' => $books->highestRatedLastMonth(),
            'highest_rated_last_6months' => $books->highestRatedLast6Months(),
            default => $books->withReviewsCount()->withAvgRated()->latest(),
        };

        $books = $books->get();

        // $cacheKey = 'books:' . $filter . ':' . $title;
        // $books =  Cache::remember($cacheKey, 5, function () use ($books, $filter) {
        //     return  $books->get();
        // });

        return view('books.index', ['books' => $books]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {

        // $book2 = Book::with([
        //     'reviews'
        // ])->where('id', $book->id)->get();

        // dd(
        //     Book::with(['reviews' => fn($query) => $query->latest()])
        //         ->find($id)->toArray()
        // );

        $cacheKey = "book:" . $book->id;
        // $book =   $book->with(['reviews' => fn($query) => $query->latest()])->withReviewsCount()->withAvgRated()->findOrFail($book->id);
        $book = cache()->remember($cacheKey, 3600, fn() =>   $book->with(['reviews' => fn($query) => $query->latest()])->withReviewsCount()->withAvgRated()->findOrFail($book->id));
        return view(
            'books.show',
            [
                'book' => $book

            ]
        );
    }



    // public function show(Book $book)
    // {
    //     return view(
    //         'books.show',
    //         ['book' => $book]
    //     );
    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
