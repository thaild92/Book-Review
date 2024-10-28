<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    use HasFactory;
    protected $fillable = ['title'];
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeWithReviewsCount(Builder $query, $from = null, $to = null)
    {
        return $query->withCount(['reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)]);
    }

    public function scopeWithAvgRated(Builder $query, $from = null, $to = null)
    {
        return $query->withAvg(['reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)], 'rating');
    }

    public function scopeTitle(Builder $query, string $title): Builder
    {
        return $query->where('title', "LIKE", '%' . $title . '%');
    }

    public function scopePopular(Builder $query, $from = null, $to = null): Builder
    {
        return $query->withReviewsCount($from, $to)
            ->orderBy('reviews_count', 'desc');
    }

    public function scopeHighestRated(Builder $query, $from = null, $to = null): Builder
    {
        return $query->withAvgRated($from, $to)
            ->orderBy('reviews_avg_rating', 'desc');
    }

    public function scopeMinReviews(Builder $query, int $minReview = 0): Builder
    {
        return $query->having('reviews_count', '>=', $minReview);
    }



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


    public function scopePopularLastMonth(Builder $query): Builder
    {
        return $query->popular(now()->subMonth(), now())
            ->highestRated(now()->subMonth(), now())
            ->minReviews(2)
        ;
    }

    public function scopePopularLast6Months(Builder $query): Builder
    {
        return $query->popular(now()->subMonths(6), now())
            ->highestRated(now()->subMonths(6), now())
            ->minReviews(5)
        ;
    }


    public function scopeHighestRatedLastMonth(Builder $query): Builder
    {


        return $query->highestRated(now()->subMonth(), now())
            ->popular(now()->subMonth(), now())
            ->minReviews(2)
        ;
    }


    public function scopeHighestRatedLast6Months(Builder $query): Builder
    {


        return $query->highestRated(now()->subMonths(6), now())
            ->popular(now()->subMonth(), now())
            ->minReviews(3)
        ;
    }

    public static function booted()
    {

        static::updated(fn(Book $book) => cache()->forget("book:" . $book->id));
        static::deleted(fn(Book $book) => cache()->forget("book:" . $book->id));
    }
}
