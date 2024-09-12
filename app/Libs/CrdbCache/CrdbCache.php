<?php

namespace App\Libs\CrdbCache;

use Illuminate\Cache\DatabaseLock;
use Illuminate\Cache\RetrievesMultipleKeys;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\QueryException;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;

class CrdbCache extends TaggableStore implements LockProvider
{
    use InteractsWithTime, RetrievesMultipleKeys;

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The database connection instance that should be used to manage locks.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $lockConnection;

    /**
     * The name of the cache table.
     *
     * @var string
     */
    protected $table;

    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The name of the cache locks table.
     *
     * @var string
     */
    protected $lockTable;

    /**
     * An array representation of the lock lottery odds.
     *
     * @var array
     */
    protected $lockLottery;

    /**
     * The default number of seconds that a lock should be held.
     *
     * @var int
     */
    protected $defaultLockTimeoutInSeconds;

    /**
     * Create a new database store.
     *
     * @param  string  $table
     * @param  string  $prefix
     * @param  string  $lockTable
     * @param  array  $lockLottery
     * @return void
     */
    public function __construct(ConnectionInterface $connection,
        $table,
        $prefix = '',
        $lockTable = 'cache_locks',
        $lockLottery = [2, 100],
        $defaultLockTimeoutInSeconds = 86400)
    {
        $this->table = $table;
        $this->prefix = $prefix;
        $this->connection = $connection;
        $this->lockTable = $lockTable;
        $this->lockLottery = $lockLottery;
        $this->defaultLockTimeoutInSeconds = $defaultLockTimeoutInSeconds;
    }

    public function get($key)
    {
        $prefixed = $this->prefix.$key;

        $cache = $this->table()->where('key', '=', $prefixed)->first();

        // If we have a cache record we will check the expiration time against current
        // time on the system and see if the record has expired. If it has, we will
        // remove the records from the database table so it isn't returned again.
        if (is_null($cache)) {
            return;
        }

        $cache = is_array($cache) ? (object) $cache : $cache;

        // If this cache expiration date is past the current time, we will remove this
        // item from the cache. Then we will return a null value since the cache is
        // expired. We will use "Carbon" to make this comparison with the column.
        if ($this->currentTime() >= $cache->expiration) {
            $this->forget($key);

            return;
        }

        return $this->unserialize($cache->value);
    }

    public function many(array $keys)
    {
        // TODO: Implement many() method.
    }

    public function put($key, $value, $seconds)
    {
        $key = $this->prefix.$key;
        $value = $this->serialize($value);
        $expiration = $this->getTime() + $seconds;

        try {
            return $this->table()->insert(compact('key', 'value', 'expiration'));
        } catch (\Exception) {
            $result = $this->table()->where('key', $key)->update(compact('value', 'expiration'));

            return $result > 0;
        }
    }

    public function add($key, $value, $seconds)
    {
        $key = $this->prefix.$key;
        $value = $this->serialize($value);
        $expiration = $this->getTime() + $seconds;

        try {
            return $this->table()->insert(compact('key', 'value', 'expiration'));
        } catch (QueryException) {
            return $this->table()
                ->where('key', $key)
                ->where('expiration', '<=', $this->getTime())
                ->update([
                    'value' => $value,
                    'expiration' => $expiration,
                ]) >= 1;
        }
    }

    public function putMany(array $values, $seconds)
    {
        // TODO: Implement putMany() method.
    }

    public function increment($key, $value = 1)
    {
        return $this->incrementOrDecrement($key, $value, function ($current, $value) {
            return $current + $value;
        });
    }

    public function decrement($key, $value = 1)
    {
        return $this->incrementOrDecrement($key, $value, function ($current, $value) {
            return $current - $value;
        });
    }

    protected function incrementOrDecrement($key, $value, \Closure $callback)
    {
        return $this->connection->transaction(function () use ($key, $value, $callback) {
            $prefixed = $this->prefix.$key;

            $cache = $this->table()->where('key', $prefixed)
                ->lockForUpdate()->first();

            // If there is no value in the cache, we will return false here. Otherwise the
            // value will be decrypted and we will proceed with this function to either
            // increment or decrement this value based on the given action callbacks.
            if (is_null($cache)) {
                return false;
            }

            $cache = is_array($cache) ? (object) $cache : $cache;

            $current = $this->unserialize($cache->value);

            // Here we'll call this callback function that was given to the function which
            // is used to either increment or decrement the function. We use a callback
            // so we do not have to recreate all this logic in each of the functions.
            $new = $callback((int) $current, $value);

            if (! is_numeric($current)) {
                return false;
            }

            // Here we will update the values in the table. We will also encrypt the value
            // since database cache values are encrypted by default with secure storage
            // that can't be easily read. We will return the new value after storing.
            $this->table()->where('key', $prefixed)->update([
                'value' => $this->serialize($new),
            ]);

            return $new;
        });
    }

    protected function getTime()
    {
        return $this->currentTime();
    }

    public function forever($key, $value)
    {
        return $this->put($key, $value, 315360000);
    }

    public function lock($name, $seconds = 0, $owner = null)
    {
        return new DatabaseLock(
            $this->lockConnection ?? $this->connection,
            $this->lockTable,
            $this->prefix.$name,
            $seconds,
            $owner,
            $this->lockLottery,
            $this->defaultLockTimeoutInSeconds
        );
    }

    public function restoreLock($name, $owner)
    {
        return $this->lock($name, 0, $owner);
    }

    public function forget($key)
    {
        $this->table()->where('key', '=', $this->prefix.$key)->delete();

        return true;
    }

    public function flush()
    {
        $this->table()->delete();

        return true;
    }

    protected function table()
    {
        return $this->connection->table($this->table);
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function setLockConnection($connection)
    {
        $this->lockConnection = $connection;

        return $this;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    protected function serialize($value)
    {
        $result = serialize($value);

        if ($this->connection instanceof PostgresConnection && str_contains($result, "\0")) {
            $result = base64_encode($result);
        }

        return $result;
    }

    protected function unserialize($value)
    {
        if ($this->connection instanceof PostgresConnection && ! Str::contains($value, [':', ';'])) {
            $value = base64_decode($value);
        }

        return unserialize($value);
    }
}
