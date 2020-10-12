<?php


namespace Laravel\Passport;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CacheTokenRepository extends TokenRepository
{

    /**
     * The key for caching queries.
     *
     * @var string
     */
    protected $cache_key = 'passport-token';

    /**
     * Get a token by the given ID.
     *
     * @param string $id
     * @return Token
     */
    public function find($id)
    {
        return Cache::remember("$this->cache_key-id-$id", now()->addMinutes(30), function () use ($id) {
            return parent::find($id);
        });
    }


    /**
     * Get a token by the given user ID and token ID.
     *
     * @param string $id
     * @param int $userId
     * @return Token|null
     */
    public function findForUser($id, $userId)
    {
        return Cache::remember("$this->cache_key-id-$id-user-$userId", now()->addMinutes(30), function () use ($id, $userId) {
            return parent::findForUser($id, $userId);
        });
    }

    /**
     * Get the token instances for the given user ID.
     *
     * @param mixed $userId
     * @return Collection
     */
    public function forUser($userId)
    {
        return Cache::remember("$this->cache_key-user-$userId", now()->addMinutes(30), function () use ($userId) {
            return parent::forUser($userId);
        });
    }
}
