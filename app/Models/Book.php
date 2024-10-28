<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    use HasFactory;
    protected $fillable = ['title'];

    /**
     * Define a one-to-many relationship with the Review model.
     *
     * This method returns all reviews associated with the book.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The reviews relationship.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scope a query to include the count of reviews for each book.
     *
     * This method adds a `reviews_count` attribute to the models, filtering 
     * the reviews based on an optional date range defined by $from and $to.
     *
     * @param string|null $from The starting date for filtering (optional).
     * @param string|null $to The ending date for filtering (optional).
     * @return \Illuminate\Database\Eloquent\Builder The updated query builder.
     */
    public function scopeWithReviewsCount(Builder $query, $from = null, $to = null)
    {
        return $query->withCount(['reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)]);
    }

    /**
     * Scope a query to include the average rating of reviews for each book.
     *
     * This method calculates the average rating of reviews, filtering 
     * based on an optional date range defined by $from and $to.
     *
     * @return \Illuminate\Database\Eloquent\Builder The updated query builder.
     */
    public function scopeWithAvgRated(Builder $query, $from = null, $to = null)
    {
        return $query->withAvg(['reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)], 'rating');
    }


    /**
     * Scope a query to filter books by title.
     *
     * This method allows searching for books that contain the specified 
     * title.
     *
     * @return \Illuminate\Database\Eloquent\Builder The updated query builder.
     */
    public function scopeTitle(Builder $query, string $title): Builder
    {
        return $query->where('title', "LIKE", '%' . $title . '%');
    }

    /**
     * Scope a query to order books by popularity based on review count.
     *
     * This method adds the review count to the query and orders the results 
     * in descending order by the number of reviews.
     *
     * @return \Illuminate\Database\Eloquent\Builder The updated query builder.
     */
    public function scopePopular(Builder $query, $from = null, $to = null): Builder
    {
        return $query->withReviewsCount($from, $to)
            ->orderBy('reviews_count', 'desc');
    }

    /**
     * Scope a query to order books by their highest average rating.
     *
     * This method calculates the average rating of reviews and orders 
     * the results in descending order by the average rating.
     *
     * @return \Illuminate\Database\Eloquent\Builder The updated query builder.
     */
    public function scopeHighestRated(Builder $query, $from = null, $to = null): Builder
    {
        return $query->withAvgRated($from, $to)
            ->orderBy('reviews_avg_rating', 'desc');
    }

    /**
     * Scope a query to filter books with a minimum number of reviews.
     *
     * This method ensures that only books with reviews greater than or 
     * equal to the specified minimum are included in the results.
     *
     * @return \Illuminate\Database\Eloquent\Builder The updated query builder.
     */

    public function scopeMinReviews(Builder $query, int $minReview = 0): Builder
    {
        return $query->having('reviews_count', '>=', $minReview);
    }


    /**
     * Apply a date range filter to the query based on the created_at field.
     *
     * This method modifies the query to include records created within the
     * specified date range defined by $from and $to.
     *
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder.
     */
    private function dateRangeFilter(Builder $query, $from = null, $to = null)
    {
        if ($from && !$to) {
            $query->where('created_at', '>=', $from);
        } elseif (!$from && $to) {
            $query->where('created_at', '<=', $to);
        } elseif ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }
    }

    /**
     * Scope a query to find popular books from the last month.
     *
     * This method retrieves books that are popular and highly rated 
     * requiring a minimum of 2 reviews.
     *
     * @return \Illuminate\Database\Eloquent\Builder The updated query builder.
     */
    public function scopePopularLastMonth(Builder $query): Builder
    {
        return $query->popular(now()->subMonth(), now())
            ->highestRated(now()->subMonth(), now())
            ->minReviews(2)
        ;
    }

    /**
     * Scope a query to find popular books from the last 6 months.
     *
     * This method retrieves books that are popular and highly rated 
     * requiring a minimum of 5 reviews.
     *
     * @return \Illuminate\Database\Eloquent\Builder The updated query builder.
     */
    public function scopePopularLast6Months(Builder $query): Builder
    {
        return $query->popular(now()->subMonths(6), now())
            ->highestRated(now()->subMonths(6), now())
            ->minReviews(5)
        ;
    }

    /**
     * Scope a query to find the highest-rated books from the last month.
     *
     * This method retrieves books that are highly rated and popular 
     * requiring a minimum of 2 reviews.
     *
     * @return \Illuminate\Database\Eloquent\Builder The updated query builder.
     */
    public function scopeHighestRatedLastMonth(Builder $query): Builder
    {


        return $query->highestRated(now()->subMonth(), now())
            ->popular(now()->subMonth(), now())
            ->minReviews(2)
        ;
    }

    /**
     * Scope a query to find the highest-rated books from the last 6 months.
     *
     * This method retrieves books that are highly rated and popular 
     * requiring a minimum of 3 reviews.
     *
     * @return \Illuminate\Database\Eloquent\Builder The updated query builder.
     */
    public function scopeHighestRatedLast6Months(Builder $query): Builder
    {


        return $query->highestRated(now()->subMonths(6), now())
            ->popular(now()->subMonth(), now())
            ->minReviews(3)
        ;
    }

    /**
     * The "booted" method of the Book model.
     *
     * Clears the cache for the book whenever it is updated or deleted.
     */
    public static function booted()
    {

        static::updated(fn(Book $book) => cache()->forget("book:" . $book->id));
        static::deleted(fn(Book $book) => cache()->forget("book:" . $book->id));
    }
}
